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


    // public function progres(Request $request)
    // {
    //     // Fetch active BulanTahun for defaults
    //     $activeBulanTahun = BulanTahun::where('aktif', 1)->first();
    //     $defaultBulan = $activeBulanTahun ? $activeBulanTahun->bulan : '01';
    //     $defaultTahun = $activeBulanTahun ? $activeBulanTahun->tahun : now()->year;
    //     $defaultKdLevel = '01'; // Harga Konsumen Kota
    //     $defaultKdWilayah = ''; // All regions
    //     $defaultStatus = 'all';

    //     Log::info('Progres Request Filters', [
    //         'bulan' => $request->input('bulan', $defaultBulan),
    //         'tahun' => $request->input('tahun', $defaultTahun),
    //         'kd_level' => $request->input('kd_level', $defaultKdLevel),
    //         'kd_wilayah' => $request->input('kd_wilayah', $defaultKdWilayah),
    //         'status' => $request->input('status', $defaultStatus),
    //     ]);

    //     if (!$request->has('bulan')) {
    //         return redirect()->route('rekon.progres', [
    //             'bulan' => $defaultBulan,
    //             'tahun' => $defaultTahun,
    //             'kd_level' => $defaultKdLevel,
    //             'kd_wilayah' => $defaultKdWilayah,
    //             'status' => $defaultStatus,
    //         ]);
    //     }

    //     // Get filter inputs or use defaults
    //     $bulan = $request->input('bulan', $defaultBulan);
    //     $tahun = $request->input('tahun', $defaultTahun);
    //     $kdLevel = $request->input('kd_level', $defaultKdLevel);
    //     $kdWilayah = $request->input('kd_wilayah', $defaultKdWilayah);
    //     $status = $request->input('status', $defaultStatus);

    //     // Generate the dynamic title
    //     $title = $this->generateRekonTableTitle($request);

    //     // Find BulanTahun record
    //     $bulanTahun = BulanTahun::where('bulan', $bulan)
    //         ->where('tahun', $tahun)
    //         ->first();

    //     // Default response
    //     $response = [
    //         'rekonsiliasi' => null,
    //         'message' => 'Silakan isi filter untuk menampilkan data rekonsiliasi.',
    //         'status' => 'no_filters',
    //         'filters' => compact('bulan', 'tahun', 'kdLevel', 'kdWilayah', 'status'),
    //         'title' => $title,
    //     ];

    //     if ($bulanTahun) {
    //         // Build query with eager loading
    //         $rekonQuery = Rekonsiliasi::with(['inflasi.komoditas', 'inflasi.wilayah', 'user'])
    //             ->where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
    //             ->whereHas('inflasi', function ($query) use ($kdLevel) {
    //                 $query->where('kd_level', $kdLevel);
    //             });

    //         // Apply filters
    //         if ($kdWilayah && $kdWilayah !== '0') {
    //             $rekonQuery->whereHas('inflasi', function ($query) use ($kdWilayah) {
    //                 $query->where('kd_wilayah', $kdWilayah);
    //             });
    //         }

    //         if ($status !== 'all') {
    //             $rekonQuery->where(function ($query) use ($status) {
    //                 $status === '01' ? $query->whereNull('user_id') : $query->whereNotNull('user_id');
    //             });
    //         }

    //         // Paginate results
    //         $rekonsiliasi = $rekonQuery->paginate(75);
    //         Log::info('Rekonsiliasi Data Sample', [
    //             'count' => $rekonsiliasi->count(),
    //             'sample' => $rekonsiliasi->take(2)->toArray(),
    //         ]);

    //         // Fetch and attach opposite inflation level for '01' and '02' only
    //         if (in_array($kdLevel, ['01', '02'])) {
    //             $oppositeLevel = $kdLevel === '01' ? '02' : '01';
    //             // Fetch all opposite records matching the criteria
    //             $inflasiOpposite = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
    //                 ->where('kd_level', $oppositeLevel)
    //                 ->whereIn('kd_wilayah', $rekonsiliasi->pluck('inflasi.kd_wilayah')->unique())
    //                 ->whereIn('kd_komoditas', $rekonsiliasi->pluck('inflasi.kd_komoditas')->unique())
    //                 ->get()
    //                 ->keyBy(function ($item) {
    //                     return $item->kd_wilayah . '-' . $item->kd_komoditas; // Unique key by wilayah and komoditas
    //                 });

    //             Log::info('Inflasi Opposite Query', [
    //                 'kd_level' => $oppositeLevel,
    //                 'bulan_tahun_id' => $bulanTahun->bulan_tahun_id,
    //                 'kd_wilayah_values' => $rekonsiliasi->pluck('inflasi.kd_wilayah')->unique()->toArray(),
    //                 'kd_komoditas_values' => $rekonsiliasi->pluck('inflasi.kd_komoditas')->unique()->toArray(),
    //                 'result_count' => $inflasiOpposite->count(),
    //                 'result_sample' => $inflasiOpposite->take(2)->toArray(),
    //             ]);

    //             // Helper function to convert numeric inflasi to status
    //             $toStatus = function ($value) {
    //                 if ($value === null) return null;
    //                 return $value > 0 ? 'naik' : ($value == 0 ? 'stabil' : 'turun');
    //             };

    //             // Attach inflasi_opposite and transform values based on kd_level
    //             foreach ($rekonsiliasi as $item) {
    //                 $key = $item->inflasi->kd_wilayah . '-' . $item->inflasi->kd_komoditas;
    //                 $oppositeData = $inflasiOpposite->get($key);
    //                 $inflasiOppositeValue = $oppositeData ? $oppositeData->inflasi : null;

    //                 // Transform inflasi if kd_level = '02'
    //                 if ($kdLevel === '02') {
    //                     $item->inflasi->inflasi = $toStatus($item->inflasi->inflasi);
    //                 }

    //                 // Transform inflasi_opposite if oppositeLevel = '02'
    //                 if ($oppositeLevel === '02') {
    //                     $item->inflasi->inflasi_opposite = $toStatus($inflasiOppositeValue);
    //                 } else {
    //                     $item->inflasi->inflasi_opposite = $inflasiOppositeValue;
    //                 }
    //             }
    //         }

    //         Log::info('Rekonsiliasi with Inflasi Opposite Sample', [
    //             'sample' => $rekonsiliasi->take(2)->toArray(),
    //         ]);

    //         // Update response
    //         $response['rekonsiliasi'] = $rekonsiliasi;
    //         $response['message'] = $rekonsiliasi->isEmpty() ? 'Tidak ada data untuk filter ini.' : 'Data berhasil dimuat.';
    //         $response['status'] = $rekonsiliasi->isEmpty() ? 'no_data' : ($rekonsiliasi->first()->user_id ? 'sudah_diisi' : 'belum_diisi');
    //         $response['title'] = $title;
    //     } elseif (!$bulanTahun) {
    //         $response['message'] = 'Periode tidak ditemukan.';
    //         $response['status'] = 'no_period';
    //     }

    //     Log::info('Final Response', [
    //         'rekonsiliasi_count' => $response['rekonsiliasi'] ? $response['rekonsiliasi']->count() : 0,
    //         'message' => $response['message'],
    //         'status' => $response['status'],
    //         'title' => $response['title'],
    //     ]);

    //     return view('rekonsiliasi.progres', $response);
    // }

    public function progres(Request $request)
    {
        // Fetch active BulanTahun
        $activeBulanTahun = BulanTahun::where('aktif', 1)->first();
        if (!$activeBulanTahun) {
            return view('rekonsiliasi.progres', [
                'rekonsiliasi' => null,
                'message' => 'Tidak ada periode aktif.',
                'status' => 'no_period',
                'title' => 'Rekonsiliasi',
                'sidebar' => [],
                'filters' => [],
            ]);
        }

        // Defaults
        $defaultBulan = $activeBulanTahun->bulan;
        $defaultTahun = $activeBulanTahun->tahun;
        $defaultKdLevel = '01'; // Harga Konsumen Kota
        $defaultStatus = 'all';

        // Initialize response
        $response = [
            'rekonsiliasi' => null,
            'message' => 'Silakan isi filter untuk menampilkan data rekonsiliasi.',
            'status' => 'no_filters',
            'filters' => [],
            'title' => 'Rekonsiliasi',
        ];

        // User type handling
        $user = auth()->user();
        $userKdWilayah = $user->kd_wilayah;

        if ($user->isPusat()) {
            // Pusat: No restrictions
            $defaultKdWilayah = ''; // All regions
            Log::info('Progres Request Filters (Pusat)', [
                'bulan' => $request->input('bulan', $defaultBulan),
                'tahun' => $request->input('tahun', $defaultTahun),
                'kd_level' => $request->input('kd_level', $defaultKdLevel),
                'kd_wilayah' => $request->input('kd_wilayah', $defaultKdWilayah),
                'status' => $request->input('status', $defaultStatus),
            ]);

            // Redirect to defaults if no filters provided
            if (!$request->has('bulan')) {
                return redirect()->route('rekon.progres', [
                    'bulan' => $defaultBulan,
                    'tahun' => $defaultTahun,
                    'kd_level' => $defaultKdLevel,
                    'kd_wilayah' => $defaultKdWilayah,
                    'status' => $defaultStatus,
                ]);
            }

            $bulan = $request->input('bulan', $defaultBulan);
            $tahun = $request->input('tahun', $defaultTahun);
            $kdLevel = $request->input('kd_level', $defaultKdLevel);
            $kdWilayah = $request->input('kd_wilayah', $defaultKdWilayah);
            $status = $request->input('status', $defaultStatus);

            $response['title'] = $this->generateRekonTableTitle($request);
        } else {
            // Non-Pusat: Restrict to active BulanTahun
            $bulan = $defaultBulan;
            $tahun = $defaultTahun;
            $kdLevel = $request->input('kd_level', $defaultKdLevel);
            $status = $request->input('status', $defaultStatus);

            if (strlen($userKdWilayah) === 2) {
                // Provinsi: Allow their kd_wilayah + kabkot under it
                $kdWilayah = $request->input('kd_wilayah', $userKdWilayah); // Default to provinsi
                $response['title'] = 'Rekonsiliasi - Provinsi';
            } elseif (strlen($userKdWilayah) === 4) {
                // Kabkot: Restrict to user's kd_wilayah
                $kdWilayah = $userKdWilayah;
                $response['title'] = 'Rekonsiliasi - Kabupaten/Kota';
            } else {
                return view('rekonsiliasi.progres', array_merge($response, [
                    'message' => 'Wilayah pengguna tidak valid.',
                    'status' => 'invalid_wilayah',
                ]));
            }
        }

        // Find BulanTahun record
        $bulanTahun = BulanTahun::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        if ($bulanTahun) {
            // Build query with eager loading
            $rekonQuery = Rekonsiliasi::with(['inflasi.komoditas', 'inflasi.wilayah', 'user'])
                ->where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->whereHas('inflasi', function ($query) use ($kdLevel) {
                    $query->where('kd_level', $kdLevel);
                });

            // Apply wilayah filter
            if ($user->isPusat() && $kdWilayah && $kdWilayah !== '0') {
                $rekonQuery->whereHas('inflasi', function ($query) use ($kdWilayah) {
                    $query->where('kd_wilayah', $kdWilayah);
                });
            } elseif (!$user->isPusat()) {
                if (strlen($userKdWilayah) === 4) {
                    // Kabkot: Exact match
                    $rekonQuery->whereHas('inflasi', function ($query) use ($userKdWilayah) {
                        $query->where('kd_wilayah', $userKdWilayah);
                    });
                } elseif (strlen($userKdWilayah) === 2) {
                    // Provinsi: Filter by selected kd_wilayah (provinsi or kabkot)
                    if ($kdWilayah === $userKdWilayah) {
                        // Provinsi: Exact match
                        $rekonQuery->whereHas('inflasi', function ($query) use ($kdWilayah) {
                            $query->where('kd_wilayah', $kdWilayah);
                        });
                    } else {
                        // Kabkot under provinsi: Exact match for selected kabkot
                        $rekonQuery->whereHas('inflasi', function ($query) use ($kdWilayah, $userKdWilayah) {
                            $query->where('kd_wilayah', $kdWilayah)
                                ->whereRaw("LEFT(kd_wilayah, 2) = ?", [$userKdWilayah]);
                        });
                    }
                }
            }

            // Apply status filter
            if ($status !== 'all') {
                $rekonQuery->where(function ($query) use ($status) {
                    $status === '01' ? $query->whereNull('user_id') : $query->whereNotNull('user_id');
                });
            }

            // Paginate results with SQL Server-compatible sorting
            $rekonsiliasi = $rekonQuery->paginate(75);

            // Fetch opposite inflation level for '01' and '02'
            if (in_array($kdLevel, ['01', '02'])) {
                $oppositeLevel = $kdLevel === '01' ? '02' : '01';
                $inflasiOpposite = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                    ->where('kd_level', $oppositeLevel)
                    ->whereIn('kd_wilayah', $rekonsiliasi->pluck('inflasi.kd_wilayah')->unique())
                    ->whereIn('kd_komoditas', $rekonsiliasi->pluck('inflasi.kd_komoditas')->unique())
                    ->get()
                    ->keyBy(function ($item) {
                        return $item->kd_wilayah . '-' . $item->kd_komoditas;
                    });

                $toStatus = function ($value) {
                    if ($value === null) return null;
                    return $value > 0 ? 'naik' : ($value == 0 ? 'stabil' : 'turun');
                };

                foreach ($rekonsiliasi as $item) {
                    $key = $item->inflasi->kd_wilayah . '-' . $item->kd_komoditas;
                    $oppositeData = $inflasiOpposite->get($key);
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
            $response['rekonsiliasi'] = $rekonsiliasi;
            $response['message'] = $rekonsiliasi->isEmpty() ? 'Tidak ada data untuk filter ini.' : 'Data berhasil dimuat.';
            $response['status'] = $rekonsiliasi->isEmpty() ? 'no_data' : ($rekonsiliasi->first()->user_id ? 'sudah_diisi' : 'belum_diisi');
            $response['filters'] = compact('bulan', 'tahun', 'kdLevel', 'kdWilayah', 'status');
        } else {
            $response['message'] = 'Periode tidak ditemukan.';
            $response['status'] = 'no_period';
        }

        Log::info('Final Response', [
            'rekonsiliasi_count' => $response['rekonsiliasi'] ? $response['rekonsiliasi']->count() : 0,
            'message' => $response['message'],
            'status' => $response['status'],
            'title' => $response['title'],
        ]);

        return view('rekonsiliasi.progres', $response);
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
