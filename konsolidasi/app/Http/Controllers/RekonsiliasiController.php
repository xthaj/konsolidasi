<?php

namespace App\Http\Controllers;

use App\Http\Resources\PengisianRekonsiliasiResource;
use App\Http\Resources\PembahasanDataResource;
use App\Models\LevelHarga;
use App\Models\Wilayah;
use App\Models\Inflasi;
use App\Models\BulanTahun;
use App\Models\Komoditas;
use App\Models\User;
use App\Models\Rekonsiliasi;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Http\Requests\FetchRekonsiliasiDataRequest;
use App\Http\Requests\UpdateRekonsiliasiRequest;
use App\Services\UserService;
use Exception;
use Illuminate\Support\Facades\Cache;

class RekonsiliasiController extends Controller
{
    protected $userService;

    //  inject UserService
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // Pemilihan
    public function pemilihan()
    {
        return view('rekonsiliasi.pemilihan');
    }

    // konfirmasi komoditas rekon
    public function confirmRekonsiliasi(Request $request)
    {
        Log::info('Confirm rekonsiliasi started', ['request' => $request->all()]);

        try {
            // Validate input
            $validated = $request->validate([
                'inflasi_ids' => 'required|array|min:1',
                'inflasi_ids.*' => 'required|integer|exists:inflasi,inflasi_id',
                'bulan_tahun_ids' => 'required|array|min:1',
                'bulan_tahun_ids.*' => 'required|integer|exists:bulan_tahun,bulan_tahun_id', // Adjust table/column as needed
            ]);

            // Ensure arrays have the same length
            if (count($validated['inflasi_ids']) !== count($validated['bulan_tahun_ids'])) {
                Log::warning('Mismatched array lengths', [
                    'inflasi_ids_count' => count($validated['inflasi_ids']),
                    'bulan_tahun_ids_count' => count($validated['bulan_tahun_ids'])
                ]);
                return response()->json([
                    'message' => 'Jumlah inflasi_ids dan bulan_tahun_ids tidak sesuai.',
                    'data' => null
                ], 422);
            }

            DB::beginTransaction();

            // Check for duplicates based on inflasi_id
            $existingIds = Rekonsiliasi::whereIn('inflasi_id', $validated['inflasi_ids'])
                ->pluck('inflasi_id')
                ->toArray();

            $duplicates = [];
            $createdCount = 0;
            $inputPairs = array_combine($validated['inflasi_ids'], $validated['bulan_tahun_ids']);

            // Fetch details for duplicate reporting
            $inflasiDetails = DB::table('inflasi')
                ->join('wilayah', 'inflasi.kd_wilayah', '=', 'wilayah.kd_wilayah')
                ->join('komoditas', 'inflasi.kd_komoditas', '=', 'komoditas.kd_komoditas')
                ->whereIn('inflasi.inflasi_id', $validated['inflasi_ids'])
                ->select(
                    'inflasi.inflasi_id as inflasi_id',
                    'wilayah.nama_wilayah',
                    'komoditas.nama_komoditas'
                )
                ->get()
                ->keyBy('inflasi_id')
                ->toArray();

            foreach ($inputPairs as $inflasi_id => $bulan_tahun_id) {
                if (in_array($inflasi_id, $existingIds)) {
                    $duplicates[] = [
                        'inflasi_id' => $inflasi_id,
                        'nama_wilayah' => $inflasiDetails[$inflasi_id]->nama_wilayah ?? 'Unknown',
                        'nama_komoditas' => $inflasiDetails[$inflasi_id]->nama_komoditas ?? 'Unknown',
                    ];
                    continue;
                }

                // Log each insert attempt
                Log::debug('Attempting to insert Rekonsiliasi', [
                    'inflasi_id' => $inflasi_id,
                    'bulan_tahun_id' => $bulan_tahun_id
                ]);

                Rekonsiliasi::create([
                    'inflasi_id' => $inflasi_id,
                    'bulan_tahun_id' => $bulan_tahun_id,
                    'created_at' => now(),
                ]);
                $createdCount++;
            }

            DB::commit();

            // Prepare response
            if (!empty($duplicates)) {
                $duplicateList = implode(', ', array_map(fn($d) => "{$d['nama_wilayah']} - {$d['nama_komoditas']}", $duplicates));
                return response()->json([
                    'message' => "Pemilihan komoditas rekonsiliasi berhasil untuk {$createdCount} entri. " .
                        count($duplicates) . " entri dilewati karena sudah ada: {$duplicateList}.",
                    'data' => null
                ], 200);
            }

            return response()->json([
                'message' => "Pemilihan komoditas rekonsiliasi berhasil untuk {$createdCount} entri.",
                'data' => null
            ], 200);
        } catch (ValidationException $e) {
            Log::warning('Validation failed in confirmRekonsiliasi', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed: ' . implode(', ', array_merge(...array_values($e->errors()))),
                'data' => null
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in confirmRekonsiliasi', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal melakukan pemilihan komoditas rekonsiliasi: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    // can be improved - delete unnecessary fields
    public function findInflasiId(Request $request)
    {
        try {
            $combinations = $request->json()->all();

            if (!is_array($combinations) || empty($combinations)) {
                return response()->json([
                    'error' => 'Invalid or empty combinations array',
                    'message' => 'Harap masukkan kombinasi data yang valid.'
                ], 400);
            }

            $results = [];

            foreach ($combinations as $combo) {
                $bulan = $combo['bulan'] ?? null;
                $tahun = $combo['tahun'] ?? null;
                $kd_level = $combo['kd_level'] ?? null;
                $kd_wilayah = $combo['kd_wilayah'] ?? null;
                $kd_komoditas = $combo['kd_komoditas'] ?? null;
                $level_harga = $combo['level_harga'] ?? 'Unknown Level Harga';
                $nama_komoditas = $combo['nama_komoditas'] ?? 'Unknown Komoditas';

                if (!$bulan || !$tahun || !$kd_level || !$kd_wilayah || !$kd_komoditas) {
                    $results[] = [
                        'kd_wilayah' => $kd_wilayah,
                        'kd_komoditas' => $kd_komoditas,
                        'level_harga' => $level_harga,
                        'nama_komoditas' => $nama_komoditas,
                        'error' => 'Missing required parameters',
                        'message' => 'Data tidak lengkap: pastikan bulan, tahun, level harga, wilayah, dan komoditas diisi.',
                        'inflasi_id' => null
                    ];
                    continue;
                }

                $bulanTahun = BulanTahun::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->first();

                if (!$bulanTahun) {
                    $results[] = [
                        'kd_wilayah' => $kd_wilayah,
                        'kd_komoditas' => $kd_komoditas,
                        'level_harga' => $level_harga,
                        'nama_komoditas' => $nama_komoditas,
                        'message' => 'Periode bulan dan tahun tidak ditemukan.',
                        'inflasi_id' => null
                    ];
                    continue;
                }

                $inflasi = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                    ->where('kd_level', $kd_level)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->first();

                $wilayah = Wilayah::where('kd_wilayah', $kd_wilayah)->first();
                $nama_wilayah = $wilayah ? $wilayah->nama_wilayah : 'Unknown Wilayah';

                if ($inflasi) {
                    $results[] = [
                        'bulan_tahun_id' => $bulanTahun->bulan_tahun_id,
                        'kd_wilayah' => $kd_wilayah,
                        'kd_komoditas' => $kd_komoditas,
                        'nama_wilayah' => $nama_wilayah,
                        'level_harga' => $level_harga,
                        'nama_komoditas' => $nama_komoditas,
                        'inflasi_id' => $inflasi->inflasi_id,
                        'inflasi' => $inflasi->nilai_inflasi ?? "0.00",
                    ];
                } else {
                    $results[] = [
                        'bulan_tahun_id' => $bulanTahun->bulan_tahun_id,
                        'kd_wilayah' => $kd_wilayah,
                        'kd_komoditas' => $kd_komoditas,
                        'nama_wilayah' => $nama_wilayah,
                        'level_harga' => $level_harga,
                        'nama_komoditas' => $nama_komoditas,
                        'message' => 'Data inflasi tidak ditemukan untuk kombinasi ini.',
                        'inflasi_id' => null
                    ];
                }
            }

            return response()->json($results, 200);
        } catch (\Exception $e) {
            Log::error('Error in findInflasiId: ' . $e->getMessage(), [
                'combinations' => $request->json()->all()
            ]);
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Terjadi kesalahan server. Silakan coba lagi nanti.'
            ], 500);
        }
    }

    // pengisian
    public function pengisian(Request $request): View
    {
        return view('rekonsiliasi.pengisian');
    }

    public function pengisian_skl(Request $request): View
    {
        return view('rekonsiliasi.pengisian_skl');
    }

    /**
     * API endpoint to fetch reconciliation progress data.
     *
     * @param Request $request HTTP request with input parameters.
     * @return \Illuminate\Http\JsonResponse JSON response with status, message, and data.
     */
    public function apipengisian(FetchRekonsiliasiDataRequest  $request)
    {
        Log::info('RekonsiliasiController@apipengisian called', ['request' => $request->all()]);
        return $this->fetchRekonsiliasiData($request, true); // JsonResponse mode
    }

    /**
     * Fetches reconciliation data with consistent JSON response.
     *
     * @param Request $request HTTP request with input parameters.
     * @param bool $isApi Unused (kept for compatibility, no pagination).
     * @param bool $unused Unused parameter for backward compatibility.
     * @return \Illuminate\Http\JsonResponse JSON response with status, message, and data.
     *
     * Retrieves `Rekonsiliasi` data based on user input, region, and period, with validation and access
     * restrictions. Uses `parent_kd` for province-city relationships and applies `flag` filters for
     * central users (`userKdWilayah = '0'`) when selecting `semua-provinsi` (`flag = 2`) or
     * `semua-kabkot` (`flag = 3`). Returns a JSON response with `message`, `status`, and
     * `data` (containing `rekonsiliasi` and `title`).
     *
     * Use Cases:
     * - Central admin: Access all provinces (`flag = 2`) or cities (`flag = 3`) with `semua-provinsi`/`semua-kabkot`.
     * - Province user: Access their province or its cities (via `parent_kd`), active period only.
     * - City user: Access their city only, active period only.
     * - Invalid inputs: Return error with appropriate HTTP status.
     */
    private function fetchRekonsiliasiData(FetchRekonsiliasiDataRequest $request, bool $isApi = false, bool $unused = false)
    {
        try {
            $errorResponse = fn(string $message, int $code) => response()->json([
                'message' => $message,
                'data' => ['rekonsiliasi' => null, 'title' => 'Rekonsiliasi'],
            ], $code);

            // Early region validation
            if (in_array($request->input('level_wilayah'), ['provinsi', 'kabkot']) && !$request->input('kd_wilayah')) {
                return $errorResponse('Kode wilayah harus disediakan untuk provinsi atau kabupaten/kota.', 400);
            }
            if (in_array($request->input('level_wilayah'), ['semua-provinsi', 'semua-kabkot']) && $request->input('kd_wilayah') !== '0') {
                return $errorResponse('Kode wilayah harus 0 untuk semua provinsi atau kabupaten/kota.', 400);
            }

            // Cached user lookup
            try {
                $user = $this->userService->getCachedUser($request->input('user_id'), $isApi);
            } catch (Exception $e) {
                return $errorResponse($e->getMessage(), $e->getCode());
            }

            // Cached active BulanTahun
            $activeBulanTahun = Cache::get('bt_aktif')['bt_aktif'] ?? $errorResponse('Tidak ada periode aktif.', 400);

            // Set defaults based on user role
            $defaults = match (true) {
                $user->isPusat() => [
                    'bulan' => $activeBulanTahun->bulan,
                    'tahun' => $activeBulanTahun->tahun,
                    'kd_level' => '01',
                    'level_wilayah' => 'semua-provinsi',
                    'kd_wilayah' => '0',
                    'status_rekon' => '00',
                    'kd_komoditas' => null,
                ],
                $user->isProvinsi() => [
                    'bulan' => $activeBulanTahun->bulan,
                    'tahun' => $activeBulanTahun->tahun,
                    'kd_level' => '00',
                    'level_wilayah' => 'provinsi',
                    'kd_wilayah' => $user->kd_wilayah,
                    'status_rekon' => '00',
                    'kd_komoditas' => null,
                ],
                $user->isKabkot() => [
                    'bulan' => $activeBulanTahun->bulan,
                    'tahun' => $activeBulanTahun->tahun,
                    'kd_level' => '00',
                    'level_wilayah' => 'kabkot',
                    'kd_wilayah' => $user->kd_wilayah,
                    'status_rekon' => '00',
                    'kd_komoditas' => null,
                ],
                default => $errorResponse('Invalid user region code.', 400),
            };

            // Merge validated input
            $input = array_merge($defaults, $request->validated());

            // Validate region and level compatibility
            if ($input['level_wilayah'] === 'kabkot' && $input['kd_level'] !== '01') {
                return $errorResponse('Kabupaten/Kota hanya tersedia untuk Harga Konsumen Kota.', 400);
            }

            // Verify kd_wilayah using cached Wilayah
            if ($input['kd_wilayah'] !== '0') {
                $wilayahData = Cache::get('all_wilayah_data');
                if (!$wilayahData->contains('kd_wilayah', $input['kd_wilayah'])) {
                    return $errorResponse('Harap pilih wilayah yang valid.', 400);
                }
            }

            // Cache all Rekonsiliasi data for active period
            $cacheKey = 'rekonsiliasi_aktif';
            $rekonsiliasi = Cache::remember($cacheKey, now()->addHours(1), function () use ($activeBulanTahun) {
                return Rekonsiliasi::with([
                    'inflasi' => fn($query) => $query->select('inflasi_id', 'kd_wilayah', 'kd_komoditas', 'kd_level', 'nilai_inflasi')
                        ->with([
                            'komoditas' => fn($query) => $query->select('kd_komoditas', 'nama_komoditas'),
                            'wilayah' => fn($query) => $query->select('kd_wilayah', 'nama_wilayah', 'flag', 'parent_kd')
                        ]),
                    'user' => fn($query) => $query->select('user_id', 'nama_lengkap')
                ])->where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)->get();
            });

            // ADD // Log cache access
            Log::info('Cache access', ['key' => $cacheKey, 'hit' => Cache::has($cacheKey)]);

            $filteredRekonsiliasi = $rekonsiliasi->filter(function ($item) use ($input, $user) {
                $inflasi = $item->inflasi;
                $wilayah = $inflasi->wilayah ?? null;

                // Filter by kd_level
                if ($input['kd_level'] !== '00' && $inflasi->kd_level !== $input['kd_level']) {
                    return false;
                }

                // Filter by kd_wilayah and level_wilayah
                if ($input['kd_wilayah'] !== '0') {
                    if ($inflasi->kd_wilayah !== $input['kd_wilayah']) {
                        return false;
                    }
                } elseif ($input['level_wilayah'] === 'semua-provinsi') {
                    if (!$wilayah || $wilayah->flag != 2) {
                        return false;
                    }
                } elseif ($input['level_wilayah'] === 'semua-kabkot') {
                    if (!$wilayah || $wilayah->flag != 3) {
                        return false;
                    }
                } elseif (!$user->isPusat() && strlen($user->kd_wilayah) === 2 && $input['level_wilayah'] === 'kabkot') {
                    if (!$wilayah || ($wilayah->kd_wilayah !== $user->kd_wilayah && $wilayah->parent_kd !== $user->kd_wilayah)) {
                        return false;
                    }
                }

                // Filter by kd_komoditas
                if (!is_null($input['kd_komoditas']) && $inflasi->kd_komoditas !== $input['kd_komoditas']) {
                    return false;
                }

                // Filter by status_rekon
                if ($input['status_rekon'] !== '00') {
                    if ($input['status_rekon'] === '01' && !is_null($item->user_id)) {
                        return false;
                    } elseif ($input['status_rekon'] === '02' && is_null($item->user_id)) {
                        return false;
                    }
                }

                return true;
            });

            // Sort all level_wilayah by kd_komoditas ascending
            $filteredRekonsiliasi = $filteredRekonsiliasi->sortBy('inflasi.komoditas.kd_komoditas')->values();

            // Transform data
            $rekonsiliasiData = $isApi ? PengisianRekonsiliasiResource::collection($filteredRekonsiliasi) : $filteredRekonsiliasi;

            return response()->json([
                'message' => $filteredRekonsiliasi->isEmpty() ? 'Tidak ada data untuk filter ini.' : 'Data berhasil dimuat.',
                'data' => [
                    'rekonsiliasi' => $rekonsiliasiData,
                    'title' => $this->generateRekonTableTitle($request),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in fetchRekonsiliasiData', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memuat data: ' . $e->getMessage(),
                'data' => ['rekonsiliasi' => [], 'title' => 'Rekonsiliasi']
            ], 500);
        }
    }

    /**
     * Restrict access based on user's region and role.
     *
     * @param User $user
     * @param string $userKdWilayah
     * @param string $level_wilayah
     * @param string $kd_wilayah
     * @return \Illuminate\Http\JsonResponse|null
     */
    // private function restrictAccessByRegion($user, $userKdWilayah, $level_wilayah, $kd_wilayah)
    // {
    //     try {
    //         $errorResponse = fn(string $message, string $status, int $code) => response()->json([
    //             'message' => $message,
    //             'status' => $status,
    //             'data' => [
    //                 'rekonsiliasi' => null,
    //                 'title' => 'Rekonsiliasi'
    //             ],
    //         ], $code);

    //         if (!$user->isPusat()) {
    //             if ($user->isProvinsi()) {
    //                 // Province users: restrict to their province or its cities (via parent_kd)
    //                 if ($level_wilayah === 'semua-provinsi') {
    //                     return $errorResponse('Akses semua provinsi tidak diizinkan untuk pengguna provinsi.', 'unauthorized', 403);
    //                 }
    //                 if ($kd_wilayah !== '0' && $kd_wilayah !== $userKdWilayah) {
    //                     $wilayah = Wilayah::where('kd_wilayah', $kd_wilayah)->first();
    //                     if (!$wilayah || $wilayah->parent_kd !== $userKdWilayah) {
    //                         return $errorResponse('Akses dibatasi untuk provinsi atau kabupaten/kota di wilayah Anda.', 'unauthorized', 403);
    //                     }
    //                 }
    //             } elseif ($user->isKabkot()) {
    //                 // City users: restrict to their city and kabkot only
    //                 if ($level_wilayah !== 'kabkot') {
    //                     return $errorResponse('Akses dibatasi untuk kabupaten/kota Anda.', 'unauthorized', 403);
    //                 }
    //                 if ($kd_wilayah !== $userKdWilayah) {
    //                     return $errorResponse('Akses dibatasi untuk kabupaten/kota Anda.', 'unauthorized', 403);
    //                 }
    //             }
    //         }

    //         return null;
    //     } catch (\Exception $e) {
    //         // add here: Handle unexpected errors
    //         Log::error('Error in restrictAccessByRegion', ['message' => $e->getMessage()]);
    //         return response()->json([
    //             'message' => 'Gagal memeriksa akses: ' . $e->getMessage(),
    //             'data' => ['rekonsiliasi' => [], 'title' => 'Rekonsiliasi']
    //         ], 500);
    //     }
    // }

    /**
     * Generates a descriptive title for the reconciliation table based on request parameters.
     *
     * @param Request $request HTTP request with input parameters.
     * @return string The formatted title for the reconciliation table.
     */
    private function generateRekonTableTitle(Request $request): string
    {
        try {
            $title = 'Rekonsiliasi';

            $kdLevel = $request->input('kd_level', '01');
            $kdKomoditas = $request->input('kd_komoditas');
            $levelWilayah = $request->input('level_wilayah', 'semua-provinsi');
            $kdWilayah = $request->input('kd_wilayah', '0');
            $bulan = $request->input('bulan');
            $tahun = $request->input('tahun');

            // Append level harga
            $levelHarga = LevelHarga::getLevelHargaNameComplete($kdLevel);
            $title .= $levelHarga ? ' ' . $levelHarga : ' Semua Level Harga';

            // Append nama komoditas
            if ($kdKomoditas) {
                $namaKomoditas = Komoditas::getKomoditasName($kdKomoditas);
                $title .= $namaKomoditas ? ' ' . $namaKomoditas : ' - Semua Komoditas';
            }

            // Append wilayah
            if ($levelWilayah === 'semua') {
                $title .= ' Semua Provinsi dan Kabupaten/Kota';
            } elseif ($levelWilayah === 'semua-provinsi') {
                $title .= ' Semua Provinsi';
            } elseif ($levelWilayah === 'semua-kabkot') {
                $title .= ' Semua Kabupaten/Kota';
            } elseif ($kdWilayah && $kdWilayah !== '0') {
                $namaWilayah = Wilayah::getWilayahName($kdWilayah);
                $title .= $namaWilayah ? ' ' . $namaWilayah : ' - Wilayah Tidak Dikenal';
            } else {
                $title .= ' Wilayah Tidak Valid';
            }

            // Append bulan dan tahun
            if ($bulan && $tahun) {
                $namaBulan = BulanTahun::getBulanName($bulan);
                $title .= $namaBulan ? ' ' . $namaBulan . ' ' . $tahun : ' Periode Tidak Dikenal';
            } elseif ($bulan) {
                $namaBulan = BulanTahun::getBulanName($bulan);
                $title .= $namaBulan ? ' - ' . $namaBulan : ' - Bulan Tidak Dikenal';
            }

            return $title;
        } catch (\Exception $e) {
            // add here: Handle unexpected errors
            Log::error('Error in generateRekonTableTitle', ['message' => $e->getMessage()]);
            return 'Rekonsiliasi';
        }
    }

    public function pembahasan(): View
    {
        return view('rekonsiliasi.pembahasan');
    }

    public function fetchPembahasanData(Request $request)
    {
        try {
            // Fallback to active BulanTahun if bulan or tahun is missing
            if (!$request->filled('bulan') || !$request->filled('tahun')) {
                $aktifBulanTahun = BulanTahun::where('aktif', 1)->first();
                if ($aktifBulanTahun) {
                    $request->merge([
                        'bulan' => $aktifBulanTahun->bulan,
                        'tahun' => $aktifBulanTahun->tahun,
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Tidak ada periode aktif.',
                        'data' => [
                            'rekonsiliasi' => [],
                            'title' => 'Rekonsiliasi',
                        ],
                    ], 400);
                }
            }

            // Extract request parameters with defaults
            $input = $request->only([
                'bulan',
                'tahun',
                'kd_level',
                'kd_wilayah',
                'status_rekon',
                'kd_komoditas',
                'level_wilayah'
            ]);
            $input = array_merge([
                'kd_level' => '01',
                'kd_wilayah' => '0',
                'status_rekon' => '00',
                'kd_komoditas' => '',
                'level_wilayah' => 'semua',
            ], $input);

            // Validate request
            $validator = Validator::make($input, [
                'bulan' => [
                    'required',
                    'integer',
                    'between:1,12',
                    Rule::exists('bulan_tahun', 'bulan')->where(function ($query) use ($input) {
                        $query->where('tahun', $input['tahun']);
                    }),
                ],
                'tahun' => [
                    'required',
                    'integer',
                    'between:2000,2100',
                    Rule::exists('bulan_tahun', 'tahun')->where(function ($query) use ($input) {
                        $query->where('bulan', $input['bulan']);
                    }),
                ],
                'kd_level' => 'required|in:01,02,03,04,05',
                'kd_wilayah' => [
                    'sometimes',
                    'max:4',
                    Rule::exists('wilayah', 'kd_wilayah'),
                ],
                'status_rekon' => 'required|in:00,01,02',
                'kd_komoditas' => 'nullable|string|max:10',
                'level_wilayah' => 'required|in:semua,semua-provinsi,semua-kabkot,provinsi,kabkot',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'data' => [
                        'rekonsiliasi' => [],
                        'title' => 'Rekonsiliasi',
                    ],
                ], 422);
            }

            // Extract validated inputs
            $bulan = $input['bulan'];
            $tahun = $input['tahun'];
            $kd_level = $input['kd_level'];
            $kd_wilayah = $input['kd_wilayah'];
            $status_rekon = $input['status_rekon'];
            $kd_komoditas = $input['kd_komoditas'];
            $level_wilayah = $input['level_wilayah'];

            // Fetch BulanTahun record
            $bulanTahun = BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->first();
            if (!$bulanTahun) {
                return response()->json([
                    'message' => 'Tidak ada data tersedia untuk bulan dan tahun yang dipilih.',
                    'data' => [
                        'rekonsiliasi' => [],
                        'title' => 'Pembahasan ' . $this->generateRekonTableTitle($request),
                    ],
                ], 404);
            }

            // Build query
            $rekonQuery = Rekonsiliasi::query()
                ->select('rekonsiliasi.*')
                ->join('inflasi', 'rekonsiliasi.inflasi_id', '=', 'inflasi.inflasi_id')
                ->join('wilayah', 'inflasi.kd_wilayah', '=', 'wilayah.kd_wilayah')
                ->where('rekonsiliasi.bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->where('inflasi.kd_level', $kd_level);

            // Apply filters
            if ($kd_wilayah !== '0') {
                $rekonQuery->where('inflasi.kd_wilayah', $kd_wilayah);
            } elseif ($level_wilayah === 'semua-provinsi') {
                $rekonQuery->where('wilayah.flag', 2);
            } elseif ($level_wilayah === 'semua-kabkot') {
                $rekonQuery->where('wilayah.flag', 3);
            }

            if (!is_null($kd_komoditas) && $kd_komoditas !== '') {
                $rekonQuery->where('inflasi.kd_komoditas', $kd_komoditas);
            }

            if ($status_rekon !== '00') {
                $rekonQuery->where('rekonsiliasi.user_id', $status_rekon === '01' ? null : '!=', null);
            }

            $rekonQuery->orderBy('inflasi.kd_komoditas', 'ASC');

            // Eager load relationships
            $rekonQuery->with(['inflasi.komoditas', 'inflasi.wilayah', 'user']);

            // Execute query
            $rekonsiliasi = $rekonQuery->get();

            // Enrich data based on kd_level
            if ($kd_level === '01') {
                $inflasiData = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                    ->whereIn('kd_level', ['01', '02'])
                    ->whereIn('kd_wilayah', $rekonsiliasi->pluck('inflasi.kd_wilayah')->unique())
                    ->whereIn('kd_komoditas', $rekonsiliasi->pluck('inflasi.kd_komoditas')->unique())
                    ->select('kd_wilayah', 'kd_komoditas', 'kd_level', 'nilai_inflasi')
                    ->get()
                    ->keyBy(function ($item) {
                        return "{$item->kd_wilayah}-{$item->kd_komoditas}-{$item->kd_level}";
                    });

                // Updated loop to use keyBy structure
                $rekonsiliasi->each(function ($rekon) use ($inflasiData) {
                    $wilayah = $rekon->inflasi->kd_wilayah;
                    $komoditas = $rekon->inflasi->kd_komoditas;

                    $rekon->inflasi_kota = $inflasiData["{$wilayah}-{$komoditas}-01"]->nilai_inflasi ?? null; // edit
                    if ($rekon->inflasi_kota === null) {
                        Log::error('Missing or invalid inflasi_kota for Rekonsiliasi', [
                            'rekonsiliasi_id' => $rekon->rekonsiliasi_id,
                            'kd_wilayah' => $wilayah,
                            'kd_komoditas' => $komoditas,
                            'kd_level' => '01',
                            'bulan_tahun_id' => $rekon->bulan_tahun_id,
                        ]);
                    }

                    $rekon->inflasi_desa = $inflasiData["{$wilayah}-{$komoditas}-02"]->nilai_inflasi ?? null; // edit
                });
            } elseif ($kd_level === '02') {
                $inflasiData = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                    ->whereIn('kd_level', ['01', '02'])
                    ->whereIn('kd_wilayah', $rekonsiliasi->pluck('inflasi.kd_wilayah')->unique())
                    ->whereIn('kd_komoditas', $rekonsiliasi->pluck('inflasi.kd_komoditas')->unique())
                    ->select('kd_wilayah', 'kd_komoditas', 'kd_level', 'nilai_inflasi')
                    ->get()
                    ->keyBy(function ($item) {
                        return "{$item->kd_wilayah}-{$item->kd_komoditas}-{$item->kd_level}";
                    });

                // Updated loop to use keyBy structure
                $rekonsiliasi->each(function ($rekon) use ($inflasiData) {
                    $wilayah = $rekon->inflasi->kd_wilayah;
                    $komoditas = $rekon->inflasi->kd_komoditas;

                    $rekon->inflasi_desa = $inflasiData["{$wilayah}-{$komoditas}-02"]->nilai_inflasi ?? null; // edit
                    if ($rekon->inflasi_desa === null) {
                        Log::error('Missing or invalid inflasi_desa for Rekonsiliasi', [
                            'rekonsiliasi_id' => $rekon->rekonsiliasi_id,
                            'kd_wilayah' => $wilayah,
                            'kd_komoditas' => $komoditas,
                            'kd_level' => '02',
                            'bulan_tahun_id' => $rekon->bulan_tahun_id,
                        ]);
                    }

                    $rekon->inflasi_kota = $inflasiData["{$wilayah}-{$komoditas}-01"]->nilai_inflasi ?? null; // edit
                });
            } else {
                $rekonsiliasi->each(function ($rekon) {
                    $rekon->inflasi_kota = $rekon->inflasi->nilai_inflasi ?? null;
                    $rekon->inflasi_desa = null;

                    if ($rekon->inflasi_kota === null) {
                        Log::error('Missing or invalid inflasi_kota for Rekonsiliasi', [
                            'rekonsiliasi_id' => $rekon->rekonsiliasi_id,
                            'kd_wilayah' => $rekon->inflasi->kd_wilayah,
                            'kd_komoditas' => $rekon->inflasi->kd_komoditas,
                            'kd_level' => $rekon->inflasi->kd_level,
                            'bulan_tahun_id' => $rekon->bulan_tahun_id,
                        ]);
                    }
                });
            }

            // Return response
            $response = [
                'message' => $rekonsiliasi->isEmpty() ? 'Tidak ada data untuk filter ini.' : 'Data berhasil dimuat.',
                'data' => [
                    'rekonsiliasi' => PembahasanDataResource::collection($rekonsiliasi),
                    'title' => 'Pembahasan ' . $this->generateRekonTableTitle($request),
                ],
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            // add here: Handle unexpected errors
            Log::error('Error in fetchPembahasanData', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memuat data: ' . $e->getMessage(),
                'data' => ['rekonsiliasi' => [], 'title' => 'Rekonsiliasi']
            ], 500);
        }
    }

    public function apiPemilihan(FetchRekonsiliasiDataRequest  $request)
    {
        Log::info('RekonsiliasiController@apiPemilihan called', ['request' => $request->all()]);
        $response = $this->fetchRekonsiliasiData($request, true);
        return response()->json([
            'message' => $response['message'],
            'data' => [
                'inflasi' => PengisianRekonsiliasiResource::collection($response['rekonsiliasi']),
                'title' => $response['data']['title'],
            ],
        ], 200);
    }



    public function updatePembahasan(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'pembahasan' => 'required|boolean',
            ]);

            $rekonsiliasi = Rekonsiliasi::findOrFail($id);

            if ($rekonsiliasi->pembahasan === $validated['pembahasan']) {
                return response()->json([
                    'message' => 'Tidak ada perubahan pada status pembahasan.',
                    'data' => null
                ], 200);
            }

            $rekonsiliasi->pembahasan = $validated['pembahasan'];
            $rekonsiliasi->save();

            Log::info('Pembahasan updated', [
                'rekonsiliasi_id' => $id,
            ]);

            return response()->json([
                'message' => 'Pembahasan berhasil diperbarui.',
                'data' => null
            ], 200);
        } catch (ValidationException $e) {
            Log::warning('Validation failed in updatePembahasan', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed: ' . implode(', ', array_merge(...array_values($e->errors()))),
                'data' => null
            ], 422);
        } catch (ModelNotFoundException $e) {
            Log::warning('Rekonsiliasi not found in updatePembahasan', ['id' => $id]);
            return response()->json([
                'message' => 'Data rekonsiliasi tidak ditemukan.',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in updatePembahasan', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui pembahasan: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $rekonsiliasi = Rekonsiliasi::findOrFail($id);
            $rekonsiliasi->delete();

            $cacheKey = 'rekonsiliasi_aktif';
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'Rekonsiliasi berhasil dihapus.',
                'data' => null
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::warning('Rekonsiliasi not found in destroy', ['id' => $id]);
            return response()->json([
                'message' => 'Data rekonsiliasi tidak ditemukan.',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in destroy', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menghapus data: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Update a reconciliation record.
     */
    public function update(UpdateRekonsiliasiRequest $request, $id)
    {
        try {
            // Cached user lookup
            $user = Cache::remember("user_{$request->input('user_id', Auth::id())}", now()->addMinutes(10), function () use ($request) {
                return $request->input('user_id')
                    ? User::find($request->input('user_id')) ?? throw new ModelNotFoundException('User tidak ditemukan.')
                    : Auth::user() ?? throw new \Exception('User tidak ditemukan atau belum login.');
            });

            // Find record
            $rekonsiliasi = Rekonsiliasi::findOrFail($id);

            // Region-based authorization
            $errorResponse = fn(string $message, int $code) => response()->json([
                'message' => $message,
                'data' => null
            ], $code);

            if (!$user->isPusat()) {
                $rekonWilayah = $rekonsiliasi->inflasi->kd_wilayah;
                if ($user->isProvinsi()) {
                    $wilayahData = Cache::get('all_wilayah_data');
                    $wilayah = $wilayahData->firstWhere('kd_wilayah', $rekonWilayah);
                    if ($rekonWilayah !== $user->kd_wilayah && (!$wilayah || $wilayah->parent_kd !== $user->kd_wilayah)) {
                        return $errorResponse('Akses dibatasi untuk provinsi atau kabupaten/kota di wilayah Anda.', 403);
                    }
                } elseif ($user->isKabkot() && $rekonWilayah !== $user->kd_wilayah) {
                    return $errorResponse('Akses dibatasi untuk kabupaten/kota Anda.', 403);
                }
            }

            // Check for no changes
            $validated = $request->validated();
            $noChanges = $rekonsiliasi->alasan === $validated['alasan'] &&
                $rekonsiliasi->detail === $validated['detail'] &&
                $rekonsiliasi->media === $validated['media'] &&
                $rekonsiliasi->user_id === $user->user_id;

            if ($noChanges) {
                return response()->json([
                    'message' => 'Tidak ada perubahan pada data rekonsiliasi.',
                    'data' => null
                ], 200);
            }

            // Update with transaction and cache invalidation for active period
            DB::transaction(function () use ($rekonsiliasi, $user, $validated) {
                $rekonsiliasi->update([
                    'user_id' => $user->user_id,
                    'alasan' => $validated['alasan'],
                    'detail' => $validated['detail'],
                    'media' => $validated['media'],
                ]);

                // Invalidate and pre-warm rekonsiliasi_aktif cache
                $cacheKey = 'rekonsiliasi_aktif';
                Cache::forget($cacheKey);
                Cache::forget('dashboard_data');
                Log::info('Cleared rekonsiliasi & dashboard cache', ['key' => $cacheKey]);

                // Pre-warm cache with active period data
                $activeBulanTahun = Cache::get('bt_aktif')['bt_aktif'] ?? throw new \Exception('Tidak ada periode aktif.');
                Cache::put($cacheKey, Rekonsiliasi::with([
                    'inflasi' => fn($query) => $query->select('inflasi_id', 'kd_wilayah', 'kd_komoditas', 'kd_level')
                        ->with([
                            'komoditas' => fn($query) => $query->select('kd_komoditas', 'nama_komoditas'),
                            'wilayah' => fn($query) => $query->select('kd_wilayah', 'nama_wilayah', 'flag', 'parent_kd')
                        ]),
                    'user' => fn($query) => $query->select('user_id', 'nama_lengkap')
                ])->where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)->get(), now()->addHours(1));
            });

            return response()->json([
                'message' => 'Rekonsiliasi berhasil diperbarui.',
                'data' => null
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::warning('Resource not found in update', ['id' => $id, 'message' => $e->getMessage()]);
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in update', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui data: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
