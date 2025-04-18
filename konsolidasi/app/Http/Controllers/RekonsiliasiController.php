<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
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

    // public function pembahasan(Request $request)
    // {
    //     // Fetch active BulanTahun
    //     $activeBulanTahun = BulanTahun::where('aktif', 1)->first();
    //     if (!$activeBulanTahun) {
    //         return view('rekonsiliasi.pembahasan', [
    //             'rekonsiliasi' => null,
    //             'message' => 'Tidak ada periode aktif.',
    //             'status' => 'no_period',
    //             'title' => 'Rekonsiliasi',
    //             'filters' => [],
    //         ]);
    //     }

    //     // Define default filters
    //     $defaults = [
    //         'bulan' => $activeBulanTahun->bulan,
    //         'tahun' => $activeBulanTahun->tahun,
    //         'kd_level' => '01', // Harga Konsumen Kota
    //         'kd_wilayah' => '', // No restriction for pusat
    //         'status' => 'all',
    //         'kd_komoditas' => 'all',
    //     ];

    //     // Redirect to defaults if no query parameters (first visit)
    //     if ($request->isMethod('GET') && !$request->query()) {
    //         return redirect()->route('rekon.pembahasan', $defaults);
    //     }

    //     // Initialize response
    //     $response = [
    //         'rekonsiliasi' => null,
    //         'message' => 'Silakan isi filter untuk menampilkan data rekonsiliasi.',
    //         'status' => 'no_filters',
    //         'filters' => [],
    //         'title' => $this->generateRekonTableTitle($request),
    //     ];

    //     // Apply filters (pusat users have no restrictions)
    //     $bulan = $request->input('bulan', $defaults['bulan']);
    //     $tahun = $request->input('tahun', $defaults['tahun']);
    //     $kdLevel = $request->input('kd_level', $defaults['kd_level']);
    //     $kdWilayah = $request->input('kd_wilayah', $defaults['kd_wilayah']);
    //     $status = $request->input('status', $defaults['status']);
    //     $kdKomoditas = $request->input('kd_komoditas', $defaults['kd_komoditas']);

    //     // Log filters
    //     Log::info('Pembahasan Request Filters', [
    //         'user_type' => 'Pusat',
    //         'bulan' => $bulan,
    //         'tahun' => $tahun,
    //         'kd_level' => $kdLevel,
    //         'kd_wilayah' => $kdWilayah,
    //         'status' => $status,
    //         'kd_komoditas' => $kdKomoditas,
    //     ]);

    //     // Find BulanTahun record
    //     $bulanTahun = BulanTahun::where('bulan', $bulan)
    //         ->where('tahun', $tahun)
    //         ->first();

    //     if ($bulanTahun) {
    //         // Build query with eager loading
    //         $rekonQuery = Rekonsiliasi::with(['inflasi.komoditas', 'inflasi.wilayah', 'user'])
    //             ->where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
    //             ->whereHas('inflasi', function ($query) use ($kdLevel) {
    //                 $query->where('kd_level', $kdLevel);
    //             });

    //         // Apply wilayah filter (optional for pusat)
    //         if ($kdWilayah !== '') {
    //             $rekonQuery->whereHas('inflasi', function ($query) use ($kdWilayah) {
    //                 $query->where('kd_wilayah', $kdWilayah);
    //             });
    //         }

    //         // Apply komoditas filter
    //         if ($kdKomoditas !== 'all') {
    //             $rekonQuery->whereHas('inflasi', function ($query) use ($kdKomoditas) {
    //                 $query->where('kd_komoditas', $kdKomoditas);
    //             });
    //         }

    //         // Apply status filter
    //         if ($status !== 'all') {
    //             $rekonQuery->where(function ($query) use ($status) {
    //                 $status === '01' ? $query->whereNull('user_id') : $query->whereNotNull('user_id');
    //             });
    //         }

    //         // Paginate results
    //         $rekonsiliasi = $rekonQuery->paginate(75);

    //         // Fetch opposite inflation level for '01' and '02'
    //         if (in_array($kdLevel, ['01', '02'])) {
    //             $oppositeLevel = $kdLevel === '01' ? '02' : '01';
    //             $inflasiOpposite = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
    //                 ->where('kd_level', $oppositeLevel)
    //                 ->whereIn('kd_wilayah', $rekonsiliasi->pluck('inflasi.kd_wilayah')->unique())
    //                 ->whereIn('kd_komoditas', $rekonsiliasi->pluck('inflasi.kd_komoditas')->unique())
    //                 ->get()
    //                 ->keyBy(function ($item) {
    //                     return $item->kd_wilayah . '-' . $item->kd_komoditas;
    //                 });

    //             $toStatus = function ($value) {
    //                 if ($value === null) return null;
    //                 return $value > 0 ? 'naik' : ($value == 0 ? 'stabil' : 'turun');
    //             };

    //             foreach ($rekonsiliasi as $item) {
    //                 $key = $item->inflasi->kd_wilayah . '-' . $item->inflasi->kd_komoditas;
    //                 $oppositeData = $inflasiOpposite->get($key);
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
    //         $response['rekonsiliasi'] = $rekonsiliasi;
    //         $response['message'] = $rekonsiliasi->isEmpty() ? 'Tidak ada data untuk filter ini.' : 'Data berhasil dimuat.';
    //         $response['status'] = $rekonsiliasi->isEmpty() ? 'no_data' : ($rekonsiliasi->first()->user_id ? 'sudah_diisi' : 'belum_diisi');
    //         $response['filters'] = [
    //             'bulan' => $bulan,
    //             'tahun' => $tahun,
    //             'kdLevel' => $kdLevel,
    //             'kdWilayah' => $kdWilayah,
    //             'status' => $status,
    //             'kdKomoditas' => $kdKomoditas,
    //         ];
    //     } else {
    //         $response['message'] = 'Periode tidak ditemukan.';
    //         $response['status'] = 'no_period';
    //         $response['filters'] = $defaults;
    //     }

    //     Log::info('Final Response', [
    //         'rekonsiliasi_count' => $response['rekonsiliasi'] ? $response['rekonsiliasi']->count() : 0,
    //         'message' => $response['message'],
    //         'status' => $response['status'],
    //         'title' => $response['title'],
    //         'filters' => $response['filters'],
    //     ]);

    //     return view('rekonsiliasi.pembahasan', $response);
    // }

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
                'inflasi.inflasi',
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
        Log::info('Gen title', $request->all());

        $monthNames = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];

        $levelHargaMap = [
            '01' => 'Harga Konsumen Kota',
            '02' => 'Harga Konsumen Desa',
            '03' => 'Harga Perdagangan Besar',
            '04' => 'Harga Produsen Desa',
            '05' => 'Harga Produsen',
            'all' => 'Semua Level Harga'
        ];

        // Use $request->input() consistently
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $kd_level = $request->kd_level;
        $kd_wilayah = $request->kd_wilayah;

        // Default title
        $title = 'Inflasi';

        // Wilayah
        $wilayah = 'Nasional';
        if ($kd_wilayah !== '0') {
            $wilayahData = Wilayah::where('kd_wilayah', $kd_wilayah)->first();
            if ($wilayahData) {
                $namaWilayah = ucwords(strtolower($wilayahData->nama_wilayah)); // Capitalize first character
                $wilayah = $kd_level === '01' && strlen($kd_wilayah) > 2
                    ? "{$namaWilayah}"
                    : "Provinsi {$namaWilayah}";
            }
        }

        // Level Harga
        $levelHarga = $levelHargaMap[$kd_level] ?? '';

        // Month and Year
        $monthName = $monthNames[$bulan] ?? '';

        $title = "Rekonsiliasi {$wilayah} {$levelHarga} {$monthName} {$tahun}";
        // }

        Log::info('Generated title', ['title' => $title]);

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

    public function update(Request $request, $id)
    {
        try {
            // Log the incoming request data
            Log::info('Rekonsiliasi update request received', [
                'id' => $id,
                'request_data' => $request->all(),
                'user' => auth()->check() ? auth()->user()->toArray() : 'No authenticated user',
            ]);

            // Validate the request data
            $validated = $request->validate([
                'alasan' => 'string|max:255',
                'detail' => 'required|string|max:500',
                'media' => 'nullable|url|max:255',
            ]);

            Log::debug('Request data validated successfully', ['validated' => $validated]);

            // Find the Rekonsiliasi record
            $rekonsiliasi = Rekonsiliasi::findOrFail($id);
            Log::debug('Rekonsiliasi record found', ['rekonsiliasi_id' => $rekonsiliasi->rekonsiliasi_id]);

            // Check authentication
            if (!auth()->check()) {
                Log::warning('No authenticated user found during update');
                throw new \Exception('User not authenticated');
            }

            // Update the record
            $rekonsiliasi->alasan = $validated['alasan'];
            $rekonsiliasi->detail = $validated['detail'];
            $rekonsiliasi->media = $validated['media'] ?? null;
            $rekonsiliasi->user_id = auth()->user()->user_id; // Use user_id from authenticated user
            $rekonsiliasi->terakhir_diedit = now();

            Log::debug('Rekonsiliasi data prepared for update', [
                'alasan' => $rekonsiliasi->alasan,
                'detail' => $rekonsiliasi->detail,
                'media' => $rekonsiliasi->media,
                'user_id' => $rekonsiliasi->user_id,
                'terakhir_diedit' => $rekonsiliasi->terakhir_diedit,
            ]);

            $rekonsiliasi->save();
            Log::info('Rekonsiliasi updated successfully', ['rekonsiliasi_id' => $rekonsiliasi->rekonsiliasi_id]);

            // Return success response
            return response()->json([
                'message' => 'Rekonsiliasi updated successfully',
                'data' => $rekonsiliasi
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors
            Log::error('Validation failed during Rekonsiliasi update', [
                'id' => $id,
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log record not found
            Log::error('Rekonsiliasi record not found', ['id' => $id]);
            return response()->json(['message' => 'Rekonsiliasi not found'], 404);
        } catch (\Exception $e) {
            // Log any other errors
            Log::error('Error updating Rekonsiliasi', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error updating Rekonsiliasi', 'error' => $e->getMessage()], 500);
        }
    }
}
