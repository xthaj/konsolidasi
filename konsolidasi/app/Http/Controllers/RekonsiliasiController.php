<?php

namespace App\Http\Controllers;

use App\Models\LevelHarga;
use App\Models\Wilayah;
use App\Models\Inflasi;
use App\Models\BulanTahun;
use App\Models\Rekonsiliasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RekonsiliasiController extends Controller
{
    public function pemilihan()
    {
        return view('rekonsiliasi.pemilihan');
    }


    public function progres(Request $request)
    {
        Log::info('RekonsiliasiController@progres called', ['request' => $request->all()]);

        // Get authenticated user
        $user = auth()->user();
        $userKdWilayah = $user->kd_wilayah;

        Log::info('User Kd Wilayah', ['userKdWilayah' => $userKdWilayah]);

        // Fetch active period
        $activeBulanTahun = BulanTahun::where('aktif', 1)->first();
        if (!$activeBulanTahun) {
            return view('rekonsiliasi.progres', [
                'rekonsiliasi' => null,
                'message' => 'Tidak ada periode aktif.',
                'status' => 'no_period',
                'title' => 'Rekonsiliasi',
                'filters' => [],
            ]);
        }

        // Set default values
        $defaults = [
            'bulan' => $activeBulanTahun->bulan,
            'tahun' => $activeBulanTahun->tahun,
            'kd_level' => '01', // Default to "Harga Konsumen Kota"
            'kd_wilayah' => $user->isPusat() ? '0' : $userKdWilayah, // "Tanpa Provinsi" for national, user's region otherwise
            'status' => '00', // Default to "Semua Status"
            'kd_komoditas' => null, // Optional, no default
        ];

        // Merge request inputs with defaults
        $input = array_merge($defaults, $request->only(array_keys($defaults)));

        // Validate input
        $validated = \Validator::make($input, [
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|between:2000,2100',
            'kd_level' => 'required|in:00,01,02,03,04,05',
            'kd_wilayah' => 'required|string|max:4',
            'status' => 'required|in:00,01,02',
            'kd_komoditas' => 'nullable|string|max:10',
        ])->validate();

        // Extract validated data
        $bulan = $validated['bulan'];
        $tahun = $validated['tahun'];
        $kdLevel = $validated['kd_level'];
        $kdWilayah = $validated['kd_wilayah'];
        $status = $validated['status'];
        $kdKomoditas = $validated['kd_komoditas'];

        // Verify kd_wilayah (allow "0" for Tanpa Provinsi/Kabkot)
        if ($kdWilayah !== '0') {
            $wilayah = Wilayah::where('kd_wilayah', $kdWilayah)->first();
            if (!$wilayah) {
                return view('rekonsiliasi.progres', [
                    'rekonsiliasi' => null,
                    'message' => 'Wilayah tidak valid.',
                    'status' => 'invalid_wilayah',
                    'title' => 'Rekonsiliasi',
                    'filters' => compact('bulan', 'tahun', 'kdLevel', 'kdWilayah', 'status', 'kdKomoditas'),
                ]);
            }
        }

        // Restrict access based on user's kd_wilayah
        if (!$user->isPusat()) {
            if (strlen($userKdWilayah) === 2) { // Province
                // Allow province, its cities, or "0" (all data under province)
                if ($kdWilayah !== '0' && $kdWilayah !== $userKdWilayah && substr($kdWilayah, 0, 2) !== $userKdWilayah) {
                    return view('rekonsiliasi.progres', [
                        'rekonsiliasi' => null,
                        'message' => 'Akses wilayah tidak diizinkan.',
                        'status' => 'unauthorized',
                        'title' => 'Rekonsiliasi',
                        'filters' => compact('bulan', 'tahun', 'kdLevel', 'kdWilayah', 'status', 'kdKomoditas'),
                    ]);
                }
            } elseif (strlen($userKdWilayah) === 4) { // City
                // Only allow user's kd_wilayah
                if ($kdWilayah !== $userKdWilayah) {
                    return view('rekonsiliasi.progres', [
                        'rekonsiliasi' => null,
                        'message' => 'Akses wilayah tidak diizinkan.',
                        'status' => 'unauthorized',
                        'title' => 'Rekonsiliasi',
                        'filters' => compact('bulan', 'tahun', 'kdLevel', 'kdWilayah', 'status', 'kdKomoditas'),
                    ]);
                }
            } else {
                return view('rekon.progres', [
                    'rekonsiliasi' => null,
                    'message' => 'Wilayah pengguna tidak valid.',
                    'status' => 'invalid_wilayah',
                    'title' => 'Rekonsiliasi',
                    'filters' => compact('bulan', 'tahun', 'kdLevel', 'kdWilayah', 'status', 'kdKomoditas'),
                ]);
            }
        }

        // Find BulanTahun record
        $bulanTahun = BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->first();
        if (!$bulanTahun) {
            return view('rekonsiliasi.progres', [
                'rekonsiliasi' => null,
                'message' => 'Periode tidak ditemukan.',
                'status' => 'no_period',
                'title' => 'Rekonsiliasi',
                'filters' => compact('bulan', 'tahun', 'kdLevel', 'kdWilayah', 'status', 'kdKomoditas'),
            ]);
        }

        // Build query
        $rekonQuery = Rekonsiliasi::with(['inflasi.komoditas', 'inflasi.wilayah', 'user'])
            ->where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
            ->whereHas('inflasi', function ($query) use ($kdLevel, $kdWilayah, $userKdWilayah, $user) {
                $query->where('kd_level', $kdLevel);
                if ($kdWilayah !== '0') {
                    $query->where('kd_wilayah', $kdWilayah);
                } elseif (!$user->isPusat() && strlen($userKdWilayah) === 2) {
                    // For province users with "Tanpa Provinsi/Kabkot", include province and its cities
                    $query->where(function ($q) use ($userKdWilayah) {
                        $q->where('kd_wilayah', $userKdWilayah)
                            ->orWhereRaw("LEFT(kd_wilayah, 2) = ?", [$userKdWilayah]);
                    });
                }
            });

        // Apply komoditas filter
        if ($kdKomoditas) {
            $rekonQuery->whereHas('inflasi', function ($query) use ($kdKomoditas) {
                $query->where('kd_komoditas', $kdKomoditas);
            });
        }

        // Apply status filter
        if ($status !== '00') {
            $rekonQuery->where(function ($query) use ($status) {
                $status === '01' ? $query->whereNull('user_id') : $query->whereNotNull('user_id');
            });
        }

        // Paginate results
        $rekonsiliasi = $rekonQuery->paginate(75);

        // Prepare response
        $response = [
            'rekonsiliasi' => $rekonsiliasi,
            'message' => $rekonsiliasi->isEmpty() ? 'Tidak ada data untuk filter ini.' : 'Data berhasil dimuat.',
            'status' => $rekonsiliasi->isEmpty() ? 'no_data' : ($rekonsiliasi->first() && $rekonsiliasi->first()->user_id ? 'sudah_diisi' : 'belum_diisi'),
            'title' => $this->generateRekonTableTitle($request),
            'filters' => compact('bulan', 'tahun', 'kdLevel', 'kdWilayah', 'status', 'kdKomoditas'),
            'activeBulan' => $activeBulanTahun->bulan,
            'activeTahun' => $activeBulanTahun->tahun,
        ];

        return view('rekonsiliasi.progres', $response);
    }


    public function pembahasan(Request $request)
    {
        // Step 1: Fetch active BulanTahun
        $activeBulanTahun = BulanTahun::where('aktif', 1)->first();
        Log::info('Step 1: Active BulanTahun', ['count' => $activeBulanTahun ? 1 : 0]);
        if (!$activeBulanTahun) {
            return view('rekonsiliasi.pembahasan', [
                'success' => false,
                'message' => 'Tidak ada periode aktif.',
                'data' => [
                    'rekonsiliasi' => null,
                    'status' => 'no_period',
                    'title' => 'Rekonsiliasi',
                    'filters' => [],
                ]
            ]);
        }

        // Define default filters
        $defaults = [
            'bulan' => $activeBulanTahun->bulan,
            'tahun' => $activeBulanTahun->tahun,
            'kd_level' => '01',
            'kd_wilayah' => '',
            'status' => 'all',
            'kd_komoditas' => 'all',
        ];

        // Redirect to defaults if no query parameters
        if ($request->isMethod('GET') && !$request->query()) {
            return redirect()->route('rekon.pembahasan', $defaults);
        }

        // Apply filters
        $bulan = $request->input('bulan', $defaults['bulan']);
        $tahun = $request->input('tahun', $defaults['tahun']);
        $kdLevel = $request->input('kd_level', $defaults['kd_level']);
        $kdWilayah = $request->input('kd_wilayah', $defaults['kd_wilayah']);
        $status = $request->input('status', $defaults['status']);
        $kdKomoditas = $request->input('kd_komoditas', $defaults['kd_komoditas']);

        Log::info('Step 2: Applied Filters', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'kd_level' => $kdLevel,
            'kd_wilayah' => $kdWilayah,
            'status' => $status,
            'kd_komoditas' => $kdKomoditas,
        ]);

        // Initialize response
        $response = [
            'success' => false,
            'message' => 'Silakan isi filter untuk menampilkan data rekonsiliasi.',
            'data' => [
                'rekonsiliasi' => null,
                'status' => 'no_filters',
                'title' => $this->generateRekonTableTitle($request),
                'filters' => [],
            ]
        ];

        // Step 3: Find BulanTahun record
        $bulanTahun = BulanTahun::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();
        Log::info('Step 3: BulanTahun Record', ['count' => $bulanTahun ? 1 : 0]);

        if ($bulanTahun) {
            // Step 4: Build base query
            $rekonQuery = Rekonsiliasi::with(['inflasi.komoditas', 'inflasi.wilayah', 'user'])
                ->where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->whereHas('inflasi', function ($query) use ($kdLevel) {
                    $query->where('kd_level', $kdLevel);
                });
            $baseCount = $rekonQuery->count();
            Log::info('Step 4: Base Query Count', ['count' => $baseCount]);

            // Step 5: Apply wilayah filter
            if ($kdWilayah !== null && $kdWilayah !== '') {
                $rekonQuery->whereHas('inflasi', function ($query) use ($kdWilayah) {
                    $query->where('kd_wilayah', $kdWilayah);
                });
                $wilayahCount = $rekonQuery->count();
                Log::info('Step 5: After Wilayah Filter', ['count' => $wilayahCount]);
            } else {
                Log::info('Step 5: Wilayah Filter Skipped', ['kd_wilayah' => $kdWilayah]);
            }

            // Step 6: Apply komoditas filter
            if ($kdKomoditas !== 'all') {
                $rekonQuery->whereHas('inflasi', function ($query) use ($kdKomoditas) {
                    $query->where('kd_komoditas', $kdKomoditas);
                });
                $komoditasCount = $rekonQuery->count();
                Log::info('Step 6: After Komoditas Filter', ['count' => $komoditasCount]);
            } else {
                Log::info('Step 6: Komoditas Filter Skipped', ['kd_komoditas' => $kdKomoditas]);
            }

            // Step 7: Apply status filter
            if ($status !== 'all') {
                $rekonQuery->where(function ($query) use ($status) {
                    $status === '01' ? $query->whereNull('user_id') : $query->whereNotNull('user_id');
                });
                $statusCount = $rekonQuery->count();
                Log::info('Step 7: After Status Filter', ['count' => $statusCount]);
            } else {
                Log::info('Step 7: Status Filter Skipped', ['status' => $status]);
            }

            // Step 8: Paginate results
            $rekonsiliasi = $rekonQuery->paginate(75);
            Log::info('Step 8: Paginated Results', ['count' => $rekonsiliasi->count()]);

            // Step 9: Fetch opposite inflation level
            if (in_array($kdLevel, ['01', '02'])) {
                $oppositeLevel = $kdLevel === '01' ? '02' : '01';
                $inflasiOpposite = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                    ->where('kd_level', $oppositeLevel)
                    ->whereIn('kd_wilayah', $rekonsiliasi->pluck('inflasi.kd_wilayah')->unique())
                    ->whereIn('kd_komoditas', $rekonsiliasi->pluck('inflasi.kd_komoditas')->unique())
                    ->get();
                Log::info('Step 9: Opposite Inflation Data', ['count' => $inflasiOpposite->count()]);

                $toStatus = function ($value) {
                    if ($value === null) return null;
                    return $value > 0 ? 'naik' : ($value == 0 ? 'stabil' : 'turun');
                };

                foreach ($rekonsiliasi as $item) {
                    $key = $item->inflasi->kd_wilayah . '-' . $item->inflasi->kd_komoditas;
                    $oppositeData = $inflasiOpposite->keyBy(function ($item) {
                        return $item->kd_wilayah . '-' . $item->kd_komoditas;
                    })->get($key);
                    $inflasiOppositeValue = $oppositeData ? $oppositeData->inflasi : null;

                    if ($kdLevel === '02') {
                        $item->inflasi->inflasi = $toStatus($item->inflasi->inflasi);
                    }

                    if ($oppositeLevel === '02') {
                        $item->inflasi->inflasi_opposite = $toStatus($inflasiOppositeValue);
                    } else {
                        $item->inflasi->inflasi_opposite = $inflasiOppositeValue;
                    }
                }
            }

            // Update response
            $response['success'] = true;
            $response['message'] = $rekonsiliasi->isEmpty() ? 'Tidak ada data untuk filter ini.' : 'Data berhasil dimuat.';
            $response['data'] = [
                'rekonsiliasi' => $rekonsiliasi,
                'status' => $rekonsiliasi->isEmpty() ? 'no_data' : ($rekonsiliasi->first()->user_id ? 'sudah_diisi' : 'belum_diisi'),
                'title' => $this->generateRekonTableTitle($request),
                'filters' => [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'kdLevel' => $kdLevel,
                    'kdWilayah' => $kdWilayah,
                    'status' => $status,
                    'kdKomoditas' => $kdKomoditas,
                ]
            ];
        } else {
            $response['success'] = false;
            $response['message'] = 'Periode tidak ditemukan.';
            $response['data'] = [
                'rekonsiliasi' => null,
                'status' => 'no_period',
                'title' => 'Rekonsiliasi',
                'filters' => $defaults,
            ];
        }

        Log::info('Step 10: Final Response', [
            'success' => $response['success'],
            'message' => $response['message'],
            'rekonsiliasi_count' => $response['data']['rekonsiliasi'] ? $response['data']['rekonsiliasi']->count() : 0,
            'status' => $response['data']['status'],
            'title' => $response['data']['title'],
            'filters' => $response['data']['filters'],
        ]);

        return view('rekonsiliasi.pembahasan', $response);
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

    private function generateRekonTableTitle(Request $request): string
    {
        $title = 'Rekonsiliasi';
        $kdLevel = $request->input('kd_level', '01');
        $kdWilayah = $request->input('kd_wilayah', '');
        $bulan = $request->input('bulan', null);

        // Append level harga
        $levelHarga = LevelHarga::getLevelHargaNameComplete($kdLevel);
        if ($levelHarga) {
            $title .= ' - ' . $levelHarga;
        }

        // Append wilayah
        if ($kdWilayah && $kdWilayah !== '0') {
            $wilayah = Wilayah::where('kd_wilayah', $kdWilayah)->first();
            if ($wilayah) {
                $title .= ' - ' . $wilayah->nama_wilayah;
            } else {
                $title .= ' - Wilayah Tidak Dikenal';
            }
        } elseif ($kdWilayah === '0') {
            $title .= ' - Semua Wilayah';
        }

        // Append bulan
        if ($bulan) {
            $namaBulan = BulanTahun::getBulanName($bulan);
            if ($namaBulan) {
                $title .= ' - ' . $namaBulan;
            }
        }

        return $title;
    }

    public function destroy($id)
    {
        Log::info('Received delete');
        // Find the record or fail
        $rekonsiliasi = Rekonsiliasi::findOrFail($id);

        // Delete the record
        $rekonsiliasi->delete();

        // Redirect back with a success message
        return  response()->json(['message' => 'Deleted successfully'], 200);
    }

    // modddified
    public function update(Request $request, $id)
    {
        // Log::info('hit');
        // Log::info('Raw request input:', ['input' => $request->all(), 'raw' => $request->getContent()]);


        // Validate request data
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:user,user_id',
                'alasan' => 'required|string|max:255',
                'detail' => 'nullable|string',
                'media' => 'nullable',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log::error('Validation failed', [
            //     'errors' => $e->errors(),
            //     'input' => $request->all(),
            //     'id' => $id,
            // ]);
            throw $e; // Re-throw to maintain default behavior (returns 422 response)
        }

        // Log::info('Rekonsiliasi update request received', [
        //     'rekonsiliasi_id' => $id,
        //     'user_id' => $validated['user_id'],
        //     'request_data' => $validated,
        // ]);

        // Find the Rekonsiliasi record
        $rekonsiliasi = Rekonsiliasi::where('rekonsiliasi_id', $id)
            ->first();

        if (!$rekonsiliasi) {
            // Log::error('Rekonsiliasi record not found', [
            //     'rekonsiliasi_id' => $id,
            //     'user_id' => $validated['user_id'],
            // ]);
            return response()->json(['error' => 'Rekonsiliasi not found'], 404);
        }

        // Update the record
        $rekonsiliasi->update([
            'alasan' => $validated['alasan'],
            'detail' => $validated['detail'],
            'media' => $validated['media'],
        ]);

        // Log::info('Rekonsiliasi updated successfully', [
        //     'rekonsiliasi_id' => $id,
        //     'user_id' => $validated['user_id'],
        // ]);

        return response()->json(['message' => 'Rekonsiliasi updated successfully'], 200);
    }
}
