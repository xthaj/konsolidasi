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
use Illuminate\View\View;

class RekonsiliasiController extends Controller
{
    public function pemilihan()
    {
        return view('rekonsiliasi.pemilihan');
    }


    /**
     * Display the reconciliation progress view.
     *
     * @param Request $request HTTP request with input parameters.
     * @return View The progress view with reconciliation data and user info.
     */
    public function progres(Request $request): View
    {
        Log::info('RekonsiliasiController@progres called', ['request' => $request->all()]);
        $response = $this->fetchRekonsiliasiData($request);
        $data = $response->getData(true);
        // $user = Auth::user();
        return view('rekonsiliasi.progres', array_merge($data, [
            // 'user' => [
            //     'id' => $user->id,
            //     'kd_wilayah' => $user->kd_wilayah,
            // ],
        ]));
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

    public function apiPembahasan(Request $request)
    {
        Log::info('RekonsiliasiController@apiPembahasan called', ['request' => $request->all()]);
        return $this->fetchPembahasanData($request, true); // JsonResponse mode
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
        $errorResponse = fn(string $message, string $status, int $code) => response()->json([
            'message' => $message,
            'status' => $status,
            'data' => [
                'rekonsiliasi' => null,
                'title' => 'Rekonsiliasi'
            ],
        ], $code);

        // Step 1: Get user and region code
        $user = null;
        if ($request->input('user_id')) {
            $user = User::find($request->input('user_id'));
            if (!$user) {
                return response()->json([
                    'status' => 'user_not_found',
                    'message' => 'User tidak ditemukan.',
                    'data' => null,
                ], 404);
            }
        } else {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'unauthenticated',
                    'message' => 'User tidak ditemukan atau belum login.',
                    'data' => null,
                ], 401);
            }
        }
        $userKdWilayah = $user->kd_wilayah;

        // Step 2: Fetch active period
        $activeBulanTahun = BulanTahun::where('aktif', 1)->first();
        if (!$activeBulanTahun) {
            return $errorResponse('Tidak ada periode aktif.', 'no_period', 400);
        }

        // Step 3: Set defaults based on user region
        $defaults = match (true) {
            $userKdWilayah === '0' => [
                'bulan' => $activeBulanTahun->bulan,
                'tahun' => $activeBulanTahun->tahun,
                'kd_level' => '01',
                'level_wilayah' => 'semua-provinsi',
                'kd_wilayah' => '0',
                'status_rekon' => '00',
                'kd_komoditas' => null,
            ],
            strlen($userKdWilayah) === 2 => [
                'bulan' => $activeBulanTahun->bulan,
                'tahun' => $activeBulanTahun->tahun,
                'kd_level' => '00',
                'level_wilayah' => 'provinsi',
                'kd_wilayah' => $userKdWilayah,
                'status_rekon' => '00',
                'kd_komoditas' => null,
            ],
            strlen($userKdWilayah) === 4 => [
                'bulan' => $activeBulanTahun->bulan,
                'tahun' => $activeBulanTahun->tahun,
                'kd_level' => '00',
                'level_wilayah' => 'kabkot',
                'kd_wilayah' => $userKdWilayah,
                'status_rekon' => '00',
                'kd_komoditas' => null,
            ],
            default => $errorResponse('Invalid user region code.', 'invalid_user', 400),
        };

        // Step 4: Merge inputs
        $input = array_merge($defaults, $request->only(array_keys($defaults)));

        // Step 5: Validate inputs
        $validator = Validator::make($input, [
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|between:2000,2100',
            'kd_level' => 'required|in:00,01,02,03,04,05',
            'level_wilayah' => 'required|in:semua,semua-provinsi,semua-kabkot,provinsi,kabkot',
            'kd_wilayah' => 'required|string|max:4',
            'status_rekon' => 'required|in:00,01,02',
            'kd_komoditas' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return $errorResponse($validator->errors()->first(), 'validation_error', 400);
        }

        extract($validator->validated());

        // Step 6: Validate kd_wilayah for provinsi/kabkot and semua-provinsi/semua-kabkot
        if (in_array($level_wilayah, ['provinsi', 'kabkot']) && !$kd_wilayah) {
            return $errorResponse('Kode wilayah harus disediakan untuk provinsi atau kabupaten/kota.', 'incomplete_request', 400);
        }
        if (in_array($level_wilayah, ['semua-provinsi', 'semua-kabkot']) && $kd_wilayah !== '0') {
            return $errorResponse('Kode wilayah harus 0 untuk semua provinsi atau kabupaten/kota.', 'invalid_wilayah', 400);
        }

        // Step 7: Validate level_wilayah and kd_level
        if ($level_wilayah === 'kabkot' && $kd_level !== '01') {
            return $errorResponse('Kabupaten/Kota hanya tersedia untuk Harga Konsumen Kota.', 'invalid_wilayah', 400);
        }

        // Step 8: Restrict non-central users to active period
        if (!$user->isPusat() && ($bulan != $activeBulanTahun->bulan || $tahun != $activeBulanTahun->tahun)) {
            return $errorResponse('Akses dibatasi untuk periode aktif di wilayah Anda.', 'unauthorized', 403);
        }

        // Step 9: Verify kd_wilayah
        if ($kd_wilayah !== '0' && !Wilayah::where('kd_wilayah', $kd_wilayah)->exists()) {
            return $errorResponse('Harap pilih wilayah yang valid.', 'invalid_wilayah', 400);
        }

        // Step 10: Restrict access by user's region
        if (!$user->isPusat()) {
            if (strlen($userKdWilayah) === 2) {
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
            } elseif (strlen($userKdWilayah) === 4) {
                // City users: restrict to their city and kabkot only
                if ($level_wilayah !== 'kabkot') {
                    return $errorResponse('Akses dibatasi untuk kabupaten/kota Anda.', 'unauthorized', 403);
                }
                if ($kd_wilayah !== $userKdWilayah) {
                    return $errorResponse('Akses dibatasi untuk kabupaten/kota Anda.', 'unauthorized', 403);
                }
            }
        }

        // Step 11: Verify period exists
        $bulanTahun = BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->first();
        if (!$bulanTahun) {
            return $errorResponse('Periode tidak ditemukan.', 'no_period', 400);
        }

        // Step 12: Build query
        $rekonQuery = Rekonsiliasi::with(['inflasi.komoditas', 'inflasi.wilayah', 'user'])
            ->where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
            ->whereHas('inflasi.wilayah', function ($query) use ($kd_level, $kd_wilayah, $level_wilayah, $userKdWilayah, $user) {
                if ($kd_level !== '00') {
                    $query->where('inflasi.kd_level', $kd_level);
                }
                if ($kd_wilayah !== '0') {
                    $query->where('wilayah.kd_wilayah', $kd_wilayah);
                } elseif ($level_wilayah === 'semua') {
                    // Do nothing – fetch all wilayah regardless of flag
                } elseif ($user->isPusat() && in_array($level_wilayah, ['semua-provinsi', 'semua-kabkot'])) {
                    $query->where('wilayah.flag', $level_wilayah === 'semua-provinsi' ? 2 : 3);
                } elseif (!$user->isPusat() && strlen($userKdWilayah) === 2 && $level_wilayah === 'kabkot') {
                    $query->where(function ($q) use ($userKdWilayah) {
                        $q->where('wilayah.kd_wilayah', $userKdWilayah)
                            ->orWhere('wilayah.parent_kd', $userKdWilayah);
                    });
                }

                if ($level_wilayah === 'semua') {
                    $query->orderByRaw("
                LENGTH(wilayah.kd_wilayah) ASC,
                SUBSTRING(wilayah.kd_wilayah, 1, 2) ASC,
                wilayah.kd_wilayah ASC
            ");
                }
            });


        // Step 13: Apply commodity filter
        if ($kd_komoditas) {
            $rekonQuery->whereHas('inflasi', function ($query) use ($kd_komoditas) {
                $query->where('kd_komoditas', $kd_komoditas);
            });
        }

        // Step 14: Apply status filter
        if ($status_rekon !== '00') {
            $rekonQuery->where($status_rekon === '01' ? 'user_id' : 'user_id', $status_rekon === '01' ? null : '!=', null);
        }

        // Step 15: Execute query (no pagination)
        $rekonsiliasi = $rekonQuery->get();

        // Step 16: Transform rekonsiliasi for API
        $rekonsiliasiData = $isApi ? PengisianRekonsiliasiResource::collection($rekonsiliasi) : $rekonsiliasi;

        // Step 17: Return response
        return response()->json([
            'message' => $rekonsiliasi->isEmpty() ? 'Tidak ada data untuk filter ini.' : 'Data berhasil dimuat.',
            'status' => $rekonsiliasi->isEmpty() ? 'no_data' : ($rekonsiliasi->first()?->user_id ? 'sudah_diisi' : 'belum_diisi'),
            'data' => [
                'rekonsiliasi' => $rekonsiliasiData,
                'title' => $this->generateRekonTableTitle($request),
            ],
        ], 200);
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
    private function fetchPembahasanData(Request $request, bool $isApi = false)
    {
        // Helper: Return error response as JSON
        $errorResponse = fn(string $message, string $status, int $code) => response()->json([
            'message' => $message,
            'status' => $status,
            'data' => [
                'rekonsiliasi' => null,
                'title' => 'Rekonsiliasi',
            ],
        ], $code);

        // Step 1: Verify authenticated user
        $user = Auth::user();
        if (!$user) {
            return $errorResponse('User tidak ditemukan atau belum login.', 'unauthenticated', 401);
        }

        // Step 2: Verify pusat-level user
        if ($user->kd_wilayah !== '0') {
            return $errorResponse('Akses hanya untuk pengguna pusat.', 'unauthorized', 403);
        }

        // Step 3: Fetch active period
        $activeBulanTahun = BulanTahun::where('aktif', 1)->first();
        if (!$activeBulanTahun) {
            return $errorResponse('Tidak ada periode aktif.', 'no_period', 400);
        }

        // Step 4: Set defaults for pusat user
        $defaults = [
            'bulan' => $activeBulanTahun->bulan,
            'tahun' => $activeBulanTahun->tahun,
            'kd_level' => '01',
            'kd_wilayah' => '0',
            'status_rekon' => '00',
            'kd_komoditas' => '',
        ];

        // Step 5: Merge inputs
        $input = array_merge($defaults, $request->only(array_keys($defaults)));

        // Step 6: Validate inputs
        $validator = Validator::make($input, [
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|between:2000,2100',
            'kd_level' => 'required|in:01,02,03,04,05',
            'kd_wilayah' => 'required|string|max:4',
            'status_rekon' => 'required|in:00,01,02',
            'kd_komoditas' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return $errorResponse($validator->errors()->first(), 'validation_error', 400);
        }

        extract($validator->validated());

        // Step 7: Verify kd_wilayah exists if not '0'
        if ($kd_wilayah !== '0' && !Wilayah::where('kd_wilayah', $kd_wilayah)->exists()) {
            return $errorResponse('Harap pilih wilayah yang valid.', 'invalid_wilayah', 400);
        }

        // Step 8: Verify period exists
        $bulanTahun = BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->first();
        if (!$bulanTahun) {
            return $errorResponse('Periode tidak ditemukan.', 'no_period', 400);
        }

        // Step 9: Build query
        $rekonQuery = Rekonsiliasi::with(['inflasi.komoditas', 'inflasi.wilayah', 'user'])
            ->where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
            ->whereHas('inflasi', function ($query) use ($kd_level, $kd_wilayah) {
                $query->where('kd_level', $kd_level); // Exact match for kd_level
                if ($kd_wilayah !== '0') {
                    $query->where('kd_wilayah', $kd_wilayah);
                }
            });

        // Step 10: Apply commodity filter
        if ($kd_komoditas) {
            $rekonQuery->whereHas('inflasi', function ($query) use ($kd_komoditas) {
                $query->where('kd_komoditas', $kd_komoditas);
            });
        }

        // Step 11: Apply status_rekon filter
        if ($status_rekon !== '00') {
            $rekonQuery->where($status_rekon === '01' ? 'user_id' : 'user_id', $status_rekon === '01' ? null : '!=', null);
        }

        // Step 12: Execute query
        $rekonsiliasi = $rekonQuery->get();

        // Step 13: Enrich data based on kd_level
        if ($kd_level === '01') {
            // For kd_level = '01', fetch inflasi_kota and check for corresponding inflasi_desa
            $inflasiDataKota = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->where('kd_level', '01')
                ->whereIn('kd_wilayah', $rekonsiliasi->pluck('inflasi.kd_wilayah')->unique())
                ->whereIn('kd_komoditas', $rekonsiliasi->pluck('inflasi.kd_komoditas')->unique())
                ->get()
                ->groupBy(['kd_wilayah', 'kd_komoditas', 'kd_level']);

            $inflasiDataDesa = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->where('kd_level', '02')
                ->whereIn('kd_wilayah', $rekonsiliasi->pluck('inflasi.kd_wilayah')->unique())
                ->whereIn('kd_komoditas', $rekonsiliasi->pluck('inflasi.kd_komoditas')->unique())
                ->get()
                ->groupBy(['kd_wilayah', 'kd_komoditas', 'kd_level']);

            $rekonsiliasi->each(function ($rekon) use ($inflasiDataKota, $inflasiDataDesa) {
                $wilayah = $rekon->inflasi->kd_wilayah;
                $komoditas = $rekon->inflasi->kd_komoditas;

                // Assign inflasi_kota
                $rekon->inflasi_kota = isset($inflasiDataKota[$wilayah][$komoditas]['01'])
                    ? $inflasiDataKota[$wilayah][$komoditas]['01'][0]->nilai_inflasi
                    : null;

                if ($rekon->inflasi_kota === null) {
                    Log::error('Missing or invalid inflasi_kota for Rekonsiliasi', [
                        'rekonsiliasi_id' => $rekon->rekonsiliasi_id,
                        'kd_wilayah' => $wilayah,
                        'kd_komoditas' => $komoditas,
                        'kd_level' => '01',
                        'bulan_tahun_id' => $rekon->bulan_tahun_id,
                    ]);
                }

                // Assign inflasi_desa from kd_level = '02'
                $rekon->inflasi_desa = isset($inflasiDataDesa[$wilayah][$komoditas]['02'])
                    ? $inflasiDataDesa[$wilayah][$komoditas]['02'][0]->nilai_inflasi
                    : null;
            });
        } elseif ($kd_level === '02') {
            // For kd_level = '02', fetch inflasi_desa and check for corresponding inflasi_kota
            $inflasiDataDesa = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->where('kd_level', '02')
                ->whereIn('kd_wilayah', $rekonsiliasi->pluck('inflasi.kd_wilayah')->unique())
                ->whereIn('kd_komoditas', $rekonsiliasi->pluck('inflasi.kd_komoditas')->unique())
                ->get()
                ->groupBy(['kd_wilayah', 'kd_komoditas', 'kd_level']);

            $inflasiDataKota = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->where('kd_level', '01')
                ->whereIn('kd_wilayah', $rekonsiliasi->pluck('inflasi.kd_wilayah')->unique())
                ->whereIn('kd_komoditas', $rekonsiliasi->pluck('inflasi.kd_komoditas')->unique())
                ->get()
                ->groupBy(['kd_wilayah', 'kd_komoditas', 'kd_level']);

            $rekonsiliasi->each(function ($rekon) use ($inflasiDataDesa, $inflasiDataKota) {
                $wilayah = $rekon->inflasi->kd_wilayah;
                $komoditas = $rekon->inflasi->kd_komoditas;

                // Assign inflasi_desa
                $rekon->inflasi_desa = isset($inflasiDataDesa[$wilayah][$komoditas]['02'])
                    ? $inflasiDataDesa[$wilayah][$komoditas]['02'][0]->nilai_inflasi
                    : null;

                if ($rekon->inflasi_desa === null) {
                    Log::error('Missing or invalid inflasi_desa for Rekonsiliasi', [
                        'rekonsiliasi_id' => $rekon->rekonsiliasi_id,
                        'kd_wilayah' => $wilayah,
                        'kd_komoditas' => $komoditas,
                        'kd_level' => '02',
                        'bulan_tahun_id' => $rekon->bulan_tahun_id,
                    ]);
                }

                // Assign inflasi_kota from kd_level = '01'
                $rekon->inflasi_kota = isset($inflasiDataKota[$wilayah][$komoditas]['01'])
                    ? $inflasiDataKota[$wilayah][$komoditas]['01'][0]->nilai_inflasi
                    : null;
            });
        } else {
            // For kd_level = '03', '04', '05', use nilai_inflasi as inflasi_kota
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

        // Step 14: Return JSON response
        return response()->json([
            'message' => $rekonsiliasi->isEmpty() ? 'Tidak ada data untuk filter ini.' : 'Data berhasil dimuat.',
            'status' => $rekonsiliasi->isEmpty() ? 'no_data' : ($rekonsiliasi->first()?->user_id ? 'sudah_diisi' : 'belum_diisi'),
            'data' => [
                'rekonsiliasi' => PembahasanDataResource::collection($rekonsiliasi),
                'title' => 'Pembahasan ' . $this->generateRekonTableTitle($request),
            ],
        ], 200);
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

    // public function pembahasan(Request $request)
    // {
    //     // Step 1: Fetch active BulanTahun
    //     $activeBulanTahun = BulanTahun::where('aktif', 1)->first();
    //     Log::info('Step 1: Active BulanTahun', ['count' => $activeBulanTahun ? 1 : 0]);
    //     if (!$activeBulanTahun) {
    //         return view('rekonsiliasi.pembahasan', [
    //             'success' => false,
    //             'message' => 'Tidak ada periode aktif.',
    //             'data' => [
    //                 'rekonsiliasi' => null,
    //                 'status' => 'no_period',
    //                 'title' => 'Rekonsiliasi',
    //                 'filters' => [],
    //             ]
    //         ]);
    //     }

    //     // Define default filters
    //     $defaults = [
    //         'bulan' => $activeBulanTahun->bulan,
    //         'tahun' => $activeBulanTahun->tahun,
    //         'kd_level' => '01',
    //         'kd_wilayah' => '',
    //         'status' => 'all',
    //         'kd_komoditas' => 'all',
    //     ];

    //     // Redirect to defaults if no query parameters
    //     if ($request->isMethod('GET') && !$request->query()) {
    //         return redirect()->route('rekon.pembahasan', $defaults);
    //     }

    //     // Apply filters
    //     $bulan = $request->input('bulan', $defaults['bulan']);
    //     $tahun = $request->input('tahun', $defaults['tahun']);
    //     $kdLevel = $request->input('kd_level', $defaults['kd_level']);
    //     $kdWilayah = $request->input('kd_wilayah', $defaults['kd_wilayah']);
    //     $status = $request->input('status', $defaults['status']);
    //     $kdKomoditas = $request->input('kd_komoditas', $defaults['kd_komoditas']);

    //     Log::info('Step 2: Applied Filters', [
    //         'bulan' => $bulan,
    //         'tahun' => $tahun,
    //         'kd_level' => $kdLevel,
    //         'kd_wilayah' => $kdWilayah,
    //         'status' => $status,
    //         'kd_komoditas' => $kdKomoditas,
    //     ]);

    //     // Initialize response
    //     $response = [
    //         'success' => false,
    //         'message' => 'Silakan isi filter untuk menampilkan data rekonsiliasi.',
    //         'data' => [
    //             'rekonsiliasi' => null,
    //             'status' => 'no_filters',
    //             'title' => $this->generateRekonTableTitle($request),
    //             'filters' => [],
    //         ]
    //     ];

    //     // Step 3: Find BulanTahun record
    //     $bulanTahun = BulanTahun::where('bulan', $bulan)
    //         ->where('tahun', $tahun)
    //         ->first();
    //     Log::info('Step 3: BulanTahun Record', ['count' => $bulanTahun ? 1 : 0]);

    //     if ($bulanTahun) {
    //         // Step 4: Build base query
    //         $rekonQuery = Rekonsiliasi::with(['inflasi.komoditas', 'inflasi.wilayah', 'user'])
    //             ->where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
    //             ->whereHas('inflasi', function ($query) use ($kdLevel) {
    //                 $query->where('kd_level', $kdLevel);
    //             });
    //         $baseCount = $rekonQuery->count();
    //         Log::info('Step 4: Base Query Count', ['count' => $baseCount]);

    //         // Step 5: Apply wilayah filter
    //         if ($kdWilayah !== null && $kdWilayah !== '') {
    //             $rekonQuery->whereHas('inflasi', function ($query) use ($kdWilayah) {
    //                 $query->where('kd_wilayah', $kdWilayah);
    //             });
    //             $wilayahCount = $rekonQuery->count();
    //             Log::info('Step 5: After Wilayah Filter', ['count' => $wilayahCount]);
    //         } else {
    //             Log::info('Step 5: Wilayah Filter Skipped', ['kd_wilayah' => $kdWilayah]);
    //         }

    //         // Step 6: Apply komoditas filter
    //         if ($kdKomoditas !== 'all') {
    //             $rekonQuery->whereHas('inflasi', function ($query) use ($kdKomoditas) {
    //                 $query->where('kd_komoditas', $kdKomoditas);
    //             });
    //             $komoditasCount = $rekonQuery->count();
    //             Log::info('Step 6: After Komoditas Filter', ['count' => $komoditasCount]);
    //         } else {
    //             Log::info('Step 6: Komoditas Filter Skipped', ['kd_komoditas' => $kdKomoditas]);
    //         }

    //         // Step 7: Apply status filter
    //         if ($status !== 'all') {
    //             $rekonQuery->where(function ($query) use ($status) {
    //                 $status === '01' ? $query->whereNull('user_id') : $query->whereNotNull('user_id');
    //             });
    //             $statusCount = $rekonQuery->count();
    //             Log::info('Step 7: After Status Filter', ['count' => $statusCount]);
    //         } else {
    //             Log::info('Step 7: Status Filter Skipped', ['status' => $status]);
    //         }

    //         // Step 8: Paginate results
    //         $rekonsiliasi = $rekonQuery->paginate(75);
    //         Log::info('Step 8: Paginated Results', ['count' => $rekonsiliasi->count()]);

    //         // Step 9: Fetch opposite inflation level
    //         if (in_array($kdLevel, ['01', '02'])) {
    //             $oppositeLevel = $kdLevel === '01' ? '02' : '01';
    //             $inflasiOpposite = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
    //                 ->where('kd_level', $oppositeLevel)
    //                 ->whereIn('kd_wilayah', $rekonsiliasi->pluck('inflasi.kd_wilayah')->unique())
    //                 ->whereIn('kd_komoditas', $rekonsiliasi->pluck('inflasi.kd_komoditas')->unique())
    //                 ->get();
    //             Log::info('Step 9: Opposite Inflation Data', ['count' => $inflasiOpposite->count()]);

    //             $toStatus = function ($value) {
    //                 if ($value === null) return null;
    //                 return $value > 0 ? 'naik' : ($value == 0 ? 'stabil' : 'turun');
    //             };

    //             foreach ($rekonsiliasi as $item) {
    //                 $key = $item->inflasi->kd_wilayah . '-' . $item->inflasi->kd_komoditas;
    //                 $oppositeData = $inflasiOpposite->keyBy(function ($item) {
    //                     return $item->kd_wilayah . '-' . $item->kd_komoditas;
    //                 })->get($key);
    //                 $inflasiOppositeValue = $oppositeData ? $oppositeData->inflasi : null;

    //                 if ($kdLevel === '02') {
    //                     $item->inflasi->inflasi = $toStatus($item->inflasi->inflasi);
    //                 }

    //                 if ($oppositeLevel === '02') {
    //                     $item->inflasi->inflasi_opposite = $toStatus($inflasiOppositeValue);
    //                 } else {
    //                     $item->inflasi->inflasi_opposite = $inflasiOppositeValue;
    //                 }
    //             }
    //         }

    //         // Update response
    //         $response['success'] = true;
    //         $response['message'] = $rekonsiliasi->isEmpty() ? 'Tidak ada data untuk filter ini.' : 'Data berhasil dimuat.';
    //         $response['data'] = [
    //             'rekonsiliasi' => $rekonsiliasi,
    //             'status' => $rekonsiliasi->isEmpty() ? 'no_data' : ($rekonsiliasi->first()->user_id ? 'sudah_diisi' : 'belum_diisi'),
    //             'title' => $this->generateRekonTableTitle($request),
    //             'filters' => [
    //                 'bulan' => $bulan,
    //                 'tahun' => $tahun,
    //                 'kdLevel' => $kdLevel,
    //                 'kdWilayah' => $kdWilayah,
    //                 'status' => $status,
    //                 'kdKomoditas' => $kdKomoditas,
    //             ]
    //         ];
    //     } else {
    //         $response['success'] = false;
    //         $response['message'] = 'Periode tidak ditemukan.';
    //         $response['data'] = [
    //             'rekonsiliasi' => null,
    //             'status' => 'no_period',
    //             'title' => 'Rekonsiliasi',
    //             'filters' => $defaults,
    //         ];
    //     }

    //     Log::info('Step 10: Final Response', [
    //         'success' => $response['success'],
    //         'message' => $response['message'],
    //         'rekonsiliasi_count' => $response['data']['rekonsiliasi'] ? $response['data']['rekonsiliasi']->count() : 0,
    //         'status' => $response['data']['status'],
    //         'title' => $response['data']['title'],
    //         'filters' => $response['data']['filters'],
    //     ]);

    //     return view('rekonsiliasi.pembahasan', $response);
    // }


    public function pembahasan(Request $request): View
    {
        Log::info('RekonsiliasiController@pembahasan called', ['request' => $request->all()]);
        $response = $this->fetchPembahasanData($request);
        $data = $response->getData(true);
        // $user = Auth::user();
        return view('rekonsiliasi.pembahasan', array_merge($data, [
            // 'user' => [
            //     'id' => $user->id,
            //     'kd_wilayah' => $user->kd_wilayah,
            // ],
        ]));
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
                'message' => 'Pembahasan updated successfully',
                'data' => $rekonsiliasi,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating pembahasan', [
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
                'status' => 'success',
                'message' => 'Rekonsiliasi berhasil dihapus.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::warning("Rekonsiliasi with ID {$id} not found for deletion.");

            return response()->json([
                'status' => 'not_found',
                'message' => 'Data rekonsiliasi tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error deleting rekonsiliasi with ID {$id}: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus data.',
            ], 500);
        }
    }


    // modddified
    public function update(Request $request, $id)
    {
        Log::info('update hit');
        Log::info('Raw request input:', ['input' => $request->all()]);

        // Step 1: Get user and region code
        $user = null;
        if ($request->input('user_id')) {
            $user = User::find($request->input('user_id'));
            if (!$user) {
                return response()->json([
                    'status' => 'user_not_found',
                    'message' => 'User tidak ditemukan.',
                    'data' => null,
                ], 404);
            }
        } else {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'unauthenticated',
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
            // Log::error('Validation failed', [
            //     'errors' => $e->errors(),
            //     'input' => $request->all(),
            //     'id' => $id,
            // ]);
            // Laravel automatically returns a 422 response with validation errors,
            // but we can customize it to match the structure
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'data' => null,
            ], 422);
        }

        try {
            // Find the Rekonsiliasi record
            $rekonsiliasi = Rekonsiliasi::where('rekonsiliasi_id', $id)->first();

            if (!$rekonsiliasi) {
                return response()->json([
                    'status' => 'error',
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
                'status' => 'success',
                'message' => 'Rekonsiliasi berhasil diperbarui',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred',
                'data' => null,
            ], 500);
        }
    }
}
