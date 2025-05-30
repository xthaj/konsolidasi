<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\Wilayah;
use App\Models\Inflasi;
use App\Models\BulanTahun;
use App\Models\LevelHarga;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DataImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

// Add this import if the FinalImport class exists at App\Imports\FinalImport
use App\Imports\FinalImport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use App\Models\Rekonsiliasi;
use Illuminate\Support\Facades\DB;
use App\Exports\InflasiExport;
use App\Http\Resources\InflasiAllLevelResource;
use Illuminate\Support\Facades\Redirect;
use App\Http\Resources\InflasiResource;

class DataController extends Controller
{
    // unused for now
    public function edit(Request $request): View
    {
        $response = [
            'status' => 'no_filters',
            'message' => 'Silakan isi filter di sidebar untuk menampilkan data inflasi.',
            'data' => [
                'inflasi' => null,
                'title' => 'Inflasi',
                'bulan' => $request->input('bulan', ''),
                'tahun' => $request->input('tahun', ''),
                'kd_level' => $request->input('kd_level', ''),
                'kd_wilayah' => $request->input('kd_wilayah', ''),
                'kd_komoditas' => $request->input('kd_komoditas', ''),
                'sort' => $request->input('sort', 'kd_komoditas'),
                'direction' => $request->input('direction', 'asc'),
            ],
        ];

        // Check if all required filters are present
        if ($request->filled('bulan') && $request->filled('tahun') && $request->filled('kd_level') && $request->filled('kd_wilayah')) {
            $bulanTahun = BulanTahun::where('bulan', $request->input('bulan'))
                ->where('tahun', $request->input('tahun'))
                ->first();

            if (!$bulanTahun) {
                // Case: Bulan/Tahun not found
                $response['status'] = 'no_data';
                $response['message'] = 'Tidak ada data tersedia untuk bulan dan tahun yang dipilih.';
                $response['data']['title'] = $this->generateTableTitle($request);
                $response['data']['inflasi'] = null;
            } else {
                // Build the title
                $title = $this->generateTableTitle($request);
                $response['data']['title'] = $title;

                // Sorting parameters
                $sortColumn = $request->input('sort', 'kd_komoditas');
                $sortDirection = $request->input('direction', 'asc');

                if ($request->input('kd_level') === 'all') {
                    // Query for all price levels, starting with Komoditas to include all
                    $query = Komoditas::query()
                        ->leftJoin('inflasi', function ($join) use ($bulanTahun, $request) {
                            $join->on('komoditas.kd_komoditas', '=', 'inflasi.kd_komoditas')
                                ->where('inflasi.bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                                ->where('inflasi.kd_wilayah', $request->input('kd_wilayah'));
                        })
                        ->select('komoditas.kd_komoditas', 'komoditas.nama_komoditas')
                        ->selectRaw("
                            MAX(CASE WHEN inflasi.kd_level = '01' THEN inflasi.nilai_inflasi END) as inflasi_01,
                            MAX(CASE WHEN inflasi.kd_level = '01' THEN inflasi.andil END) as andil_01,
                            MAX(CASE WHEN inflasi.kd_level = '02' THEN inflasi.nilai_inflasi END) as inflasi_02,
                            MAX(CASE WHEN inflasi.kd_level = '02' THEN inflasi.andil END) as andil_02,
                            MAX(CASE WHEN inflasi.kd_level = '03' THEN inflasi.nilai_inflasi END) as inflasi_03,
                            MAX(CASE WHEN inflasi.kd_level = '03' THEN inflasi.andil END) as andil_03,
                            MAX(CASE WHEN inflasi.kd_level = '04' THEN inflasi.nilai_inflasi END) as inflasi_04,
                            MAX(CASE WHEN inflasi.kd_level = '04' THEN inflasi.andil END) as andil_04,
                            MAX(CASE WHEN inflasi.kd_level = '05' THEN inflasi.nilai_inflasi END) as inflasi_05,
                            MAX(CASE WHEN inflasi.kd_level = '05' THEN inflasi.andil END) as andil_05
                        ")
                        ->groupBy('komoditas.kd_komoditas', 'komoditas.nama_komoditas');

                    if ($request->filled('kd_komoditas')) {
                        $query->where('komoditas.kd_komoditas', $request->input('kd_komoditas'));
                    }

                    // Apply sorting
                    $query->orderBy($sortColumn, $sortDirection);

                    // Paginate
                    $inflasi = $query->paginate(20);

                    // Attach komoditas relationship manually
                    $inflasi->getCollection()->transform(function ($item) {
                        $item->komoditas = (object) [
                            'kd_komoditas' => $item->kd_komoditas,
                            'nama_komoditas' => $item->nama_komoditas,
                        ];
                        return $item;
                    });
                } else {
                    // Query for specific price level, starting with Komoditas to include all
                    $query = Komoditas::query()
                        ->leftJoin('inflasi', function ($join) use ($bulanTahun, $request) {
                            $join->on('komoditas.kd_komoditas', '=', 'inflasi.kd_komoditas')
                                ->where('inflasi.bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                                ->where('inflasi.kd_level', $request->input('kd_level'))
                                ->where('inflasi.kd_wilayah', $request->input('kd_wilayah'));
                        })
                        ->select(
                            'komoditas.kd_komoditas',
                            'komoditas.nama_komoditas',
                            'inflasi.inflasi_id',
                            'inflasi.nilai_inflasi',
                            'inflasi.andil',
                            'inflasi.kd_wilayah'
                        );

                    if ($request->filled('kd_komoditas')) {
                        $query->where('komoditas.kd_komoditas', $request->input('kd_komoditas'));
                    }

                    // Apply sorting
                    $query->orderBy($sortColumn, $sortDirection);

                    // Paginate
                    $inflasi = $query->paginate(75);

                    // Attach komoditas relationship manually
                    $inflasi->getCollection()->transform(function ($item) {
                        $item->komoditas = (object) [
                            'kd_komoditas' => $item->kd_komoditas,
                            'nama_komoditas' => $item->nama_komoditas,
                        ];
                        return $item;
                    });
                }

                if ($inflasi->isEmpty() && $request->filled('kd_komoditas')) {
                    // Case: No data for specific komoditas
                    $response['status'] = 'no_data';
                    $response['message'] = 'Tidak ada data tersedia untuk komoditas yang dipilih.';
                    $response['data']['inflasi'] = $inflasi;
                } elseif ($inflasi->total() === 0) {
                    // Case: No data but all komoditas requested
                    $response['status'] = 'no_data';
                    $response['message'] = 'Tidak ada data inflasi tersedia untuk filter tersebut, menampilkan semua komoditas.';
                    $response['data']['inflasi'] = $inflasi;
                } else {
                    // Success case
                    $response['status'] = 'success';
                    $response['message'] = 'Data ditemukan.';
                    $response['data']['inflasi'] = $inflasi;
                }

                // Update data with request inputs
                $response['data']['bulan'] = $request->input('bulan');
                $response['data']['tahun'] = $request->input('tahun');
                $response['data']['kd_level'] = $request->input('kd_level');
                $response['data']['kd_wilayah'] = $request->input('kd_wilayah');
                $response['data']['kd_komoditas'] = $request->input('kd_komoditas');
                $response['data']['sort'] = $sortColumn;
                $response['data']['direction'] = $sortDirection;
            }
        }
        return view('data.edit', $response);
    }

    public function apiEdit(Request $request)
    {
        // Fallback to active BulanTahun if bulan or tahun is missing
        if (!$request->filled('bulan') || !$request->filled('tahun')) {
            $aktifBulanTahun = BulanTahun::where('aktif', 1)->first();
            if ($aktifBulanTahun) {
                $request->merge([
                    'bulan' => $aktifBulanTahun->bulan,
                    'tahun' => $aktifBulanTahun->tahun,
                ]);
            }
        }

        // Extract request parameters with defaults
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $kd_level = $request->input('kd_level', '01');
        $kd_wilayah = $request->input('kd_wilayah', '0');
        $kd_komoditas = $request->input('kd_komoditas', '');
        $sortColumn = $request->input('sort', 'kd_komoditas');
        $sortDirection = $request->input('direction', 'asc');

        // Validate request
        $validator = Validator::make($request->all(), [
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
            'kd_level' => 'required|in:00,01,02,03,04,05',
            'kd_wilayah' => 'required|string',
            'kd_komoditas' => 'nullable|string',
            'sort' => 'in:kd_komoditas,nilai_inflasi',
            'direction' => 'in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Merge defaults back into request
        $request->merge([
            'kd_level' => $kd_level,
            'kd_wilayah' => $kd_wilayah,
        ]);

        // Initialize response
        $response = [
            'message' => 'Data ditemukan.',
            'data' => [
                'inflasi' => [],
                'title' => $this->generateTableTitle($request),
                'kd_wilayah' => $kd_wilayah, // Include kd_wilayah in response
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ];

        // Fetch BulanTahun record
        $bulanTahun = BulanTahun::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        if (!$bulanTahun) {
            return response()->json([
                'message' => 'Tidak ada data tersedia untuk bulan dan tahun yang dipilih.',
                'data' => [
                    'inflasi' => [],
                    'title' => $this->generateTableTitle($request),
                    'kd_wilayah' => $kd_wilayah,
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        }

        // Case: kd_level is '00' (all price levels)
        if ($kd_level === '00') {
            $query = Komoditas::query()
                ->leftJoin('inflasi', function ($join) use ($bulanTahun, $kd_wilayah) {
                    $join->on('komoditas.kd_komoditas', '=', 'inflasi.kd_komoditas')
                        ->where('inflasi.bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                        ->where('inflasi.kd_wilayah', $kd_wilayah);
                })
                ->select('komoditas.kd_komoditas', 'komoditas.nama_komoditas')
                ->selectRaw("
                MAX(CASE WHEN inflasi.kd_level = '01' THEN inflasi.nilai_inflasi END) as inflasi_01,
                MAX(CASE WHEN inflasi.kd_level = '01' THEN inflasi.andil END) as andil_01,
                MAX(CASE WHEN inflasi.kd_level = '02' THEN inflasi.nilai_inflasi END) as inflasi_02,
                MAX(CASE WHEN inflasi.kd_level = '02' THEN inflasi.andil END) as andil_02,
                MAX(CASE WHEN inflasi.kd_level = '03' THEN inflasi.nilai_inflasi END) as inflasi_03,
                MAX(CASE WHEN inflasi.kd_level = '03' THEN inflasi.andil END) as andil_03,
                MAX(CASE WHEN inflasi.kd_level = '04' THEN inflasi.nilai_inflasi END) as inflasi_04,
                MAX(CASE WHEN inflasi.kd_level = '04' THEN inflasi.andil END) as andil_04,
                MAX(CASE WHEN inflasi.kd_level = '05' THEN inflasi.nilai_inflasi END) as inflasi_05,
                MAX(CASE WHEN inflasi.kd_level = '05' THEN inflasi.andil END) as andil_05
            ")
                ->groupBy('komoditas.kd_komoditas', 'komoditas.nama_komoditas');

            if ($kd_komoditas) {
                $query->where('komoditas.kd_komoditas', $kd_komoditas);
            }

            // Handle sorting
            if ($sortColumn === 'nilai_inflasi') {
                // Sort by a specific aggregated column, e.g., inflasi_01 (Harga Konsumen Kota)
                $query->orderByRaw('MAX(CASE WHEN inflasi.kd_level = "01" THEN inflasi.nilai_inflasi END) ' . $sortDirection);
            } else {
                // Sort by kd_komoditas
                $query->orderBy('komoditas.kd_komoditas', $sortDirection);
            }

            $inflasi = $query->get();
        } else {
            // Case: Specific kd_level
            $query = Komoditas::query()
                ->leftJoin('inflasi', function ($join) use ($bulanTahun, $kd_level, $kd_wilayah) {
                    $join->on('komoditas.kd_komoditas', '=', 'inflasi.kd_komoditas')
                        ->where('inflasi.bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                        ->where('inflasi.kd_level', $kd_level)
                        ->where('inflasi.kd_wilayah', $kd_wilayah);
                })
                ->select(
                    'komoditas.kd_komoditas',
                    'komoditas.nama_komoditas',
                    'inflasi.inflasi_id',
                    'inflasi.nilai_inflasi',
                    'inflasi.andil',
                    'inflasi.kd_wilayah'
                );

            if ($kd_komoditas) {
                $query->where('komoditas.kd_komoditas', $kd_komoditas);
            }

            $query->orderBy($sortColumn, $sortDirection);
            $inflasi = $query->get();
        }

        if ($inflasi->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada data inflasi tersedia untuk filter tersebut.',
                'data' => [
                    'inflasi' => [],
                    'title' => $this->generateTableTitle($request),
                    'kd_wilayah' => $kd_wilayah,
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        }

        // Transform data based on kd_level
        $response['data']['inflasi'] = $kd_level === '00'
            ? InflasiAllLevelResource::collection($inflasi)
            : InflasiResource::collection($inflasi);

        return response()->json($response, 200);
    }

    /**
     * Generate the table title based on request parameters
     */
    private function generateTableTitle(Request $request): string
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $kd_level = $request->kd_level;
        $kd_wilayah = $request->kd_wilayah;

        log::info('Generating table title', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'kd_level' => $kd_level,
            'kd_wilayah' => $kd_wilayah,
        ]);

        // Default title
        $title = 'Inflasi';

        // Wilayah
        $wilayah = Wilayah::getWilayahName($kd_wilayah);

        // Level Harga
        $levelHarga = LevelHarga::getLevelHargaNameComplete($kd_level);

        // Month and Year
        $monthName = BulanTahun::getBulanName($bulan);

        $title = "Inflasi {$wilayah} {$levelHarga} {$monthName} {$tahun} (dalam persen)";

        return $title;
    }








    public function delete(Request $request, $id)
    {
        try {
            // Find the Inflasi record or fail with a 404
            $inflasi = Inflasi::findOrFail($id);

            // Delete the Inflasi record
            $inflasi->delete();

            // Return a success response with a detailed message
            // Match the response structure of apiEdit for consistency
            return response()->json([
                'status' => 'success',
                'message' => 'Data inflasi berhasil dihapus.',
                'data' => null,
                // 'meta' => [
                //     'timestamp' => now()->toIso8601String(),
                //     'api_version' => '1.0',
                // ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle case where Inflasi record is not found
            return response()->json([
                'status' => 'error',
                'message' => 'Data inflasi tidak ditemukan.',
                'data' => null,
                // 'meta' => [
                //     'timestamp' => now()->toIso8601String(),
                //     'api_version' => '1.0',
                // ],
            ], 404);
        } catch (\Exception $e) {
            // Handle other potential errors (e.g., database issues)
            // \Log::error('Delete inflasi failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data inflasi. Silakan coba lagi.',
                'data' => null,
                // 'meta' => [
                //     'timestamp' => now()->toIso8601String(),
                //     'api_version' => '1.0',
                // ],
            ], 500);
        }
    }



    public function hapus_final(Request $request)
    {
        Log::info('Hapus final method started', $request->all());

        $request->merge(['bulan' => (int) $request->bulan]);

        $validated = $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:2100',
            'level' => 'required|string|in:01,02,03,04,05',
        ]);

        $response = [
            'success' => false,
            'message' => [],
            'data' => [],
        ];

        try {
            $bulanTahun = BulanTahun::where('bulan', $validated['bulan'])
                ->where('tahun', $validated['tahun'])
                ->first();

            if (!$bulanTahun) {
                $response['message'] = ["Tidak ada data tersedia untuk periode tersebut."];
                return $request->wantsJson()
                    ? response()->json($response)
                    : redirect()->back()->with('response', $response);
            }

            // Update only the final_inflasi and final_andil columns to NULL
            $updatedRows = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->where('kd_level', $validated['level'])
                ->update([
                    'final_inflasi' => null,
                    'final_andil' => null,
                ]);

            if ($updatedRows > 0) {
                $response['success'] = true;
                $response['message'] = ["Data berhasil dihapus sebagian. Kolom final_inflasi dan final_andil diset NULL. Jumlah baris terpengaruh: $updatedRows"];
                $response['data'] = ['updated' => $updatedRows];
            } else {
                $response['message'] = ["Tidak ada data yang sesuai untuk diubah."];
            }
        } catch (\Exception $e) {
            $response['message'] = ["Terjadi kesalahan saat mengubah data: {$e->getMessage()}"];
            Log::error("Hapus final error: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
        }

        return $request->wantsJson()
            ? response()->json($response)
            : redirect()->back()->with('response', $response);
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

    public function update(Request $request, $id)
    {
        $request->validate([
            'inflasi' => 'required|numeric',
        ]);

        try {
            $inflasi = Inflasi::findOrFail($id);
            $inflasi->inflasi = $request->inflasi;
            $inflasi->save();

            return redirect()->back()->with('success', 'Data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:2100',
            'kd_level' => 'required|string|in:01,02,03,04,05',
            'kd_wilayah' => 'required|string',
            'kd_komoditas' => 'required|integer',
            'inflasi' => 'required|numeric',
        ]);

        try {
            $bulanTahun = BulanTahun::firstOrCreate([
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
            ]);

            Inflasi::create([
                'bulan_tahun_id' => $bulanTahun->bulan_tahun_id,
                'kd_level' => $request->kd_level,
                'kd_wilayah' => $request->kd_wilayah,
                'kd_komoditas' => $request->kd_komoditas,
                'inflasi' => $request->inflasi,
            ]);

            return redirect()->back()->with('success', 'Data added successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error adding data: ' . $e->getMessage());
        }
    }

    public function confirmRekonsiliasi(Request $request)
    {
        Log::info('Confirm rekonsiliasi started', ['request' => $request->all()]);

        $validated = $request->validate([
            'inflasi_ids' => 'required|array',
            'inflasi_ids.*' => 'integer',
            'bulan_tahun_ids' => 'required|array',
            'bulan_tahun_ids.*' => 'integer',
        ]);

        try {
            DB::beginTransaction();

            $existingRecords = Rekonsiliasi::whereIn('inflasi_id', $validated['inflasi_ids'])
                ->whereIn('bulan_tahun_id', $validated['bulan_tahun_ids'])
                ->select('inflasi_id', 'bulan_tahun_id')
                ->get()
                ->groupBy('bulan_tahun_id')
                ->mapWithKeys(function ($group, $bulan_tahun_id) {
                    return [$bulan_tahun_id => $group->pluck('inflasi_id')->toArray()];
                })
                ->toArray();

            $duplicates = [];
            $createdCount = 0;
            $inputPairs = array_combine($validated['inflasi_ids'], $validated['bulan_tahun_ids']);

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
                if (isset($existingRecords[$bulan_tahun_id]) && in_array($inflasi_id, $existingRecords[$bulan_tahun_id])) {
                    $duplicates[] = [
                        'inflasi_id' => $inflasi_id,
                        'bulan_tahun_id' => $bulan_tahun_id,
                        'nama_wilayah' => $inflasiDetails[$inflasi_id]->nama_wilayah ?? 'Unknown',
                        'nama_komoditas' => $inflasiDetails[$inflasi_id]->nama_komoditas ?? 'Unknown',
                    ];
                    continue;
                }

                Rekonsiliasi::create([
                    'inflasi_id' => $inflasi_id,
                    'bulan_tahun_id' => $bulan_tahun_id,
                    'terakhir_diedit' => now(),
                ]);
                $createdCount++;
            }

            DB::commit();

            if (!empty($duplicates)) {
                $duplicateList = implode(', ', array_map(fn($d) => "{$d['nama_wilayah']} - {$d['nama_komoditas']}", $duplicates));
                return response()->json([
                    'success' => true,
                    'partial_success' => true,
                    'message' => "Pemilihan komoditas rekonsiliasi berhasil untuk {$createdCount} entri. " .
                        count($duplicates) . " entri dilewati: {$duplicateList}.",
                    'duplicates' => $duplicates,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => "Pemilihan komoditas rekonsiliasi berhasil untuk {$createdCount} entri.",
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pemilihan komoditas rekonsiliasi gagal dilakukan:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pemilihan komoditas rekonsiliasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function finalisasi()
    {
        return view('data.finalisasi');
    }

    public function pengaturan()
    {
        return view('pengaturan.index');
    }


    public function export_final(Request $request)
    {
        Log::info('Export final method started', $request->all());

        $request->merge(['bulan' => (int) $request->bulan]);

        $validated = $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:2100',
            'level' => 'required|string|in:01,02,03,04,05',
        ]);

        $response = [
            'success' => false,
            'message' => [],
            'data' => [],
        ];

        try {
            $bulanTahun = BulanTahun::where('bulan', $validated['bulan'])
                ->where('tahun', $validated['tahun'])
                ->first();

            if (!$bulanTahun) {
                $response['message'] = ["Tidak ada data tersedia untuk periode tersebut."];
                return $request->wantsJson()
                    ? response()->json($response)
                    : redirect()->back()->with('response', $response);
            }

            $count = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->where('kd_level', $validated['level'])
                ->count();

            if ($count === 0) {
                $response['message'] = ["Tidak ada data yang sesuai untuk diunduh."];
                return $request->wantsJson()
                    ? response()->json($response)
                    : redirect()->back()->with('response', $response);
            }

            $namaBulan = BulanTahun::getBulanName($validated['bulan']);
            $namaLevel = LevelHarga::getLevelHargaNameShortened($validated['level']);
            $fileName = "Konsolidasi_{$namaBulan}_{$validated['tahun']}_{$namaLevel}.xlsx";
            return Excel::download(new InflasiExport($validated['bulan'], $validated['tahun'], $validated['level']), $fileName);
        } catch (\Exception $e) {
            $response['message'] = ["Terjadi kesalahan saat mengunduh data: {$e->getMessage()}"];
            Log::error("Export final error: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
            return $request->wantsJson()
                ? response()->json($response)
                : redirect()->back()->with('response', $response);
        }
    }
}
