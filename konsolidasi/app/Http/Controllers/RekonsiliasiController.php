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


class RekonsiliasiController extends Controller
{
    // Pemilihan
    public function pemilihan()
    {
        return view('rekonsiliasi.pemilihan');
    }

    // Progres
    public function progres(Request $request): View
    {
        return view('rekonsiliasi.progres');
    }

    public function progres_skl(Request $request): View
    {
        Log::info('RekonsiliasiController@progres_skl called', ['request' => $request->all()]);

        // $user = Auth::user();

        return view('rekonsiliasi.progres_skl', [
            // 'user' => [
            //     'id' => $user->id,
            //     'kd_wilayah' => $user->kd_wilayah,
            //     'is_provinsi' => $user->isProvinsi(),
            // ],
            'status' => 'no_filters',
            'message' => 'Silakan pilih filter untuk menampilkan data.',
            'data' => ['rekonsiliasi' => [], 'title' => 'Rekonsiliasi'],
        ]);
    }

    /**
     * API endpoint to fetch reconciliation progress data.
     *
     * @param Request $request HTTP request with input parameters.
     * @return \Illuminate\Http\JsonResponse JSON response with status, message, and data.
     */
    public function apiProgres(Request $request)
    {
        Log::info('RekonsiliasiController@apiProgres called', ['request' => $request->all()]);
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
    private function fetchRekonsiliasiData(Request $request, bool $isApi = false, bool $unused = false)
    {
        // Helper: Return error response
        $errorResponse = fn(string $message, int $code) => response()->json([
            'message' => $message,
            'data' => ['rekonsiliasi' => null, 'title' => 'Rekonsiliasi'],
        ], $code);

        // Step 1: Get user and region code
        $user = $request->input('user_id')
            ? User::find($request->input('user_id')) ?? $errorResponse('User tidak ditemukan.', 404)
            : Auth::user() ?? $errorResponse('User tidak ditemukan atau belum login.', 401);

        $userKdWilayah = $user->kd_wilayah;

        // Step 2: Fetch active period
        $activeBulanTahun = BulanTahun::where('aktif', 1)->first()
            ?? $errorResponse('Tidak ada periode aktif.', 400);

        // Step 3: Set defaults based on user region
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
                'kd_wilayah' => $userKdWilayah,
                'status_rekon' => '00',
                'kd_komoditas' => null,
            ],
            $user->isKabkot() => [
                'bulan' => $activeBulanTahun->bulan,
                'tahun' => $activeBulanTahun->tahun,
                'kd_level' => '00',
                'level_wilayah' => 'kabkot',
                'kd_wilayah' => $userKdWilayah,
                'status_rekon' => '00',
                'kd_komoditas' => null,
            ],
            default => $errorResponse('Invalid user region code.', 400),
        };

        // Step 4: Merge and validate inputs
        $input = array_merge($defaults, $request->only(array_keys($defaults)));
        $validator = Validator::make($input, [
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|between:2000,2100',
            'kd_level' => 'required|in:00,01,02,03,04,05',
            'level_wilayah' => 'required|in:semua,semua-provinsi,semua-kabkot,provinsi,kabkot',
            'kd_wilayah' => 'required|max:4',
            'status_rekon' => 'required|in:00,01,02',
            'kd_komoditas' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return $errorResponse($validator->errors()->first(), 400);
        }

        extract($validator->validated());

        // Step 5: Validate kd_wilayah
        if (in_array($level_wilayah, ['provinsi', 'kabkot']) && !$kd_wilayah) {
            return $errorResponse('Kode wilayah harus disediakan untuk provinsi atau kabupaten/kota.', 400);
        }
        if (in_array($level_wilayah, ['semua-provinsi', 'semua-kabkot']) && $kd_wilayah !== '0') {
            return $errorResponse('Kode wilayah harus 0 untuk semua provinsi atau kabupaten/kota.', 400);
        }

        // Step 6: Validate level_wilayah and kd_level
        if ($level_wilayah === 'kabkot' && $kd_level !== '01') {
            return $errorResponse('Kabupaten/Kota hanya tersedia untuk Harga Konsumen Kota.', 400);
        }

        // Step 7: Restrict non-central users to active period
        if (!$user->isPusat() && ($bulan != $activeBulanTahun->bulan || $tahun != $activeBulanTahun->tahun)) {
            return $errorResponse('Akses dibatasi untuk periode aktif di wilayah Anda.', 403);
        }

        // Step 8: Verify kd_wilayah
        if ($kd_wilayah !== '0' && !Wilayah::where('kd_wilayah', $kd_wilayah)->exists()) {
            return $errorResponse('Harap pilih wilayah yang valid.', 400);
        }

        // Step 9: Apply region-based access restrictions
        if ($restrictionResult = $this->restrictAccessByRegion($user, $userKdWilayah, $level_wilayah, $kd_wilayah)) {
            return $restrictionResult;
        }

        // Step 10: Verify period exists
        $bulanTahun = BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->first();
        if (!$bulanTahun) {
            return $errorResponse('Periode tidak ditemukan.', 400);
        }

        // Step 11: Build query
        $rekonQuery = Rekonsiliasi::with(['inflasi.komoditas', 'inflasi.wilayah', 'user'])
            ->where('rekonsiliasi.bulan_tahun_id', $bulanTahun->bulan_tahun_id)
            ->whereHas('inflasi.wilayah', function ($query) use ($kd_level, $kd_wilayah, $level_wilayah, $userKdWilayah, $user) {
                if ($kd_level !== '00') {
                    $query->where('inflasi.kd_level', $kd_level);
                }
                if ($kd_wilayah !== '0') {
                    $query->where('wilayah.kd_wilayah', $kd_wilayah);
                } elseif ($level_wilayah === 'semua') {
                    // No filtering
                } elseif ($user->isPusat() && in_array($level_wilayah, ['semua-provinsi', 'semua-kabkot'])) {
                    $query->where('wilayah.flag', $level_wilayah === 'semua-provinsi' ? 2 : 3);
                } elseif (!$user->isPusat() && strlen($userKdWilayah) === 2 && $level_wilayah === 'kabkot') {
                    $query->where(function ($q) use ($userKdWilayah) {
                        $q->where('wilayah.kd_wilayah', $userKdWilayah)
                            ->orWhere('wilayah.parent_kd', $userKdWilayah);
                    });
                }
            });

        if ($level_wilayah === 'semua') {
            $rekonQuery->join('inflasi', 'rekonsiliasi.inflasi_id', '=', 'inflasi.inflasi_id')
                ->join('wilayah', 'inflasi.kd_wilayah', '=', 'wilayah.kd_wilayah')
                ->orderByRaw("
                CASE 
                    WHEN wilayah.flag = 2 THEN wilayah.kd_wilayah 
                    ELSE wilayah.parent_kd 
                END ASC,
                wilayah.flag ASC,
                wilayah.kd_wilayah ASC
            ");
        }

        // Step 12: Apply commodity filter
        if ($kd_komoditas) {
            $rekonQuery->whereHas('inflasi', function ($query) use ($kd_komoditas) {
                $query->where('kd_komoditas', $kd_komoditas);
            });
        }

        // Step 13: Apply status filter
        if ($status_rekon !== '00') {
            $rekonQuery->where($status_rekon === '01' ? 'user_id' : 'user_id', $status_rekon === '01' ? null : '!=', null);
        }

        // Step 14: Execute query
        $rekonsiliasi = $rekonQuery->get();

        // Step 15: Transform data for API
        $rekonsiliasiData = $isApi ? PengisianRekonsiliasiResource::collection($rekonsiliasi) : $rekonsiliasi;

        // Step 16: Return response
        return response()->json([
            'message' => $rekonsiliasi->isEmpty() ? 'Tidak ada data untuk filter ini.' : 'Data berhasil dimuat.',
            'data' => [
                'rekonsiliasi' => $rekonsiliasiData,
                'title' => $this->generateRekonTableTitle($request),
            ],
        ], 200);
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
    private function restrictAccessByRegion($user, $userKdWilayah, $level_wilayah, $kd_wilayah)
    {
        $errorResponse = fn(string $message, string $status, int $code) => response()->json([
            'message' => $message,
            'status' => $status,
            'data' => [
                'rekonsiliasi' => null,
                'title' => 'Rekonsiliasi'
            ],
        ], $code);

        if (!$user->isPusat()) {
            if ($user->isProvinsi()) {
                // Province users: restrict to their province or its cities (via parent_kd)
                if ($level_wilayah === 'semua-provinsi') {
                    return $errorResponse('Akses semua provinsi tidak diizinkan untuk pengguna provinsi.', 'unauthorized', 403);
                }
                if ($kd_wilayah !== '0' && $kd_wilayah !== $userKdWilayah) {
                    $wilayah = Wilayah::where('kd_wilayah', $kd_wilayah)->first();
                    if (!$wilayah || $wilayah->parent_kd !== $userKdWilayah) {
                        return $errorResponse('Akses dibatasi untuk provinsi atau kabupaten/kota di wilayah Anda.', 'unauthorized', 403);
                    }
                }
            } elseif ($user->isKabkot()) {
                // City users: restrict to their city and kabkot only
                if ($level_wilayah !== 'kabkot') {
                    return $errorResponse('Akses dibatasi untuk kabupaten/kota Anda.', 'unauthorized', 403);
                }
                if ($kd_wilayah !== $userKdWilayah) {
                    return $errorResponse('Akses dibatasi untuk kabupaten/kota Anda.', 'unauthorized', 403);
                }
            }
        }

        return null; // No restrictions violated
    }

    /**
     * Generates a descriptive title for the reconciliation table based on request parameters.
     *
     * @param Request $request HTTP request with input parameters.
     * @return string The formatted title for the reconciliation table.
     */
    private function generateRekonTableTitle(Request $request): string
    {
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
    }

    public function pembahasan(): View
    {
        return view('rekonsiliasi.pembahasan');
    }

    public function fetchPembahasanData(Request $request)
    {
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

            // edit: Updated loop to use keyBy structure
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

            // edit: Updated loop to use keyBy structure
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
    }

    public function apiPemilihan(Request $request)
    {
        Log::info('RekonsiliasiController@apiPemilihan called', ['request' => $request->all()]);
        $response = $this->fetchRekonsiliasiData($request, true);
        return response()->json([
            'success' => $response['success'],
            'message' => $response['message'],
            'data' => [
                'inflasi' => PengisianRekonsiliasiResource::collection($response['rekonsiliasi']),
                'title' => $response['data']['title'],
            ],
        ]);
    }



    public function updatePembahasan(Request $request, $id)
    {
        try {
            $request->validate([
                'pembahasan' => 'required|boolean',
            ]);

            $rekonsiliasi = Rekonsiliasi::findOrFail($id);
            $rekonsiliasi->pembahasan = $request->input('pembahasan');
            $rekonsiliasi->save();

            Log::info('Pembahasan updated', [
                'rekonsiliasi_id' => $id,
                'pembahasan' => $rekonsiliasi->pembahasan,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pembahasan berhasil diperbarui.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Terjadi error saat memperbarui pembahasan', [
                'rekonsiliasi_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update pembahasan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        $rekonsiliasiData = DB::table('rekonsiliasi')
            ->leftJoin('inflasi', 'rekonsiliasi.inflasi_id', '=', 'inflasi.inflasi_id')
            ->leftJoin('wilayah', 'inflasi.kd_wilayah', '=', 'wilayah.kd_wilayah')
            ->leftJoin('komoditas', 'inflasi.kd_komoditas', '=', 'komoditas.kd_komoditas')
            ->leftJoin('user', 'rekonsiliasi.user_id', '=', 'user_id')
            ->select(
                'rekonsiliasi.rekonsiliasi_id',
                'inflasi.kd_wilayah',
                'wilayah.nama_wilayah',
                'inflasi.kd_komoditas',
                'komoditas.nama_komoditas',
                'inflasi.kd_level',
                'inflasi.nilai_inflasi',
                'rekonsiliasi.alasan',
                'rekonsiliasi.detail',
                'rekonsiliasi.media',
                'rekonsiliasi.terakhir_diedit',
                'user.nama_lengkap'
            )
            ->get();

        $levelHargaMapping = [
            '01' => 'Harga Konsumen Kota',
            '02' => 'Harga Konsumen Desa',
            '03' => 'Harga Perdagangan Besar',
            '04' => 'Harga Produsen Desa',
            '05' => 'Harga Produsen'
        ];

        return view('rekonsiliasi.index', compact('rekonsiliasiData', 'levelHargaMapping'));
    }

    public function destroy($id)
    {
        try {
            // Log::info("Attempting to delete rekonsiliasi with ID: {$id}");

            // Find the record or fail
            $rekonsiliasi = Rekonsiliasi::findOrFail($id);

            // Delete the record
            $rekonsiliasi->delete();

            // Log::info("Rekonsiliasi with ID {$id} deleted successfully.");

            return response()->json([
                'message' => 'Rekonsiliasi berhasil dihapus.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::warning("Rekonsiliasi with ID {$id} not found for deletion.");

            return response()->json([
                'message' => 'Data rekonsiliasi tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error deleting rekonsiliasi with ID {$id}: " . $e->getMessage());

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage(),
            ], 500);
        }
    }


    // modified: can take user_id

    public function update(Request $request, $id)
    {
        // Step 1: Get user and region code
        $user = null;
        if ($request->input('user_id')) {
            $user = User::find($request->input('user_id'));
            if (!$user) {
                return response()->json([
                    'message' => 'User tidak ditemukan.',
                    'data' => null,
                ], 404);
            }
        } else {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'User tidak ditemukan atau belum login.',
                    'data' => null,
                ], 401);
            }
        }
        $user_id = $user->user_id;

        // Validate request data
        try {
            $validated = $request->validate([
                'alasan' => 'required|string|max:500',
                'detail' => 'nullable|string',
                'media' => 'nullable|url',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal: ' . $e->errors()[array_key_first($e->errors())][0],
                'data' => null,
            ], 422);
        }

        try {
            // Find the Rekonsiliasi record
            $rekonsiliasi = Rekonsiliasi::where('rekonsiliasi_id', $id)->first();

            if (!$rekonsiliasi) {
                return response()->json([
                    'message' => 'Rekonsiliasi tidak ditemukan',
                    'data' => null,
                ], 404);
            }
            // Update the record
            $rekonsiliasi->update([
                'user_id' => $user_id,
                'alasan' => $validated['alasan'],
                'detail' => $validated['detail'],
                'media' => $validated['media'],
            ]);

            return response()->json([
                'message' => 'Rekonsiliasi berhasil diperbarui',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan tak terduga: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
