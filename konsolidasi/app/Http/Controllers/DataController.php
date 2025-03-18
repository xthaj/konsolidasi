<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\Wilayah;
use App\Models\Inflasi;
use App\Models\BulanTahun;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DataImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Rekonsiliasi;

class DataController extends Controller
{
    // public function index(Request $request)
    // {
    //     $query = Inflasi::query()->with('komoditas');

    //     // Apply filters if present
    //     if ($request->filled('bulan') && $request->filled('tahun')) {
    //         $periode = "{$request->tahun}-{$request->bulan}-01";
    //         $query->where('periode', $periode);
    //     }
    //     if ($request->filled('level_harga')) {
    //         $query->where('level_harga', $request->level_harga);
    //     }
    //     if ($request->filled('nasional') && $request->nasional == '1') {
    //         $query->where('kd_wilayah', '1'); // Nasional
    //     } elseif ($request->filled('kd_wilayah')) {
    //         $query->where('kd_wilayah', $request->kd_wilayah);
    //     }
    //     if ($request->filled('kd_komoditas')) {
    //         $query->where('kd_komoditas', $request->kd_komoditas);
    //     }

    //     $inflasi = $request->hasAny(['bulan', 'tahun', 'level_harga', 'kd_wilayah', 'kd_komoditas', 'nasional'])
    //         ? $query->paginate(10)
    //         : collect(); // Empty if no filters

    //     $komoditas = Komoditas::all();
    //     return view('your-two-panel-page', compact('inflasi', 'komoditas'));
    // }

    public function edit(Request $request): View
{
    // Default response
    $response = [
        'inflasi' => null,
        'message' => 'Silakan isi filter di sidebar untuk menampilkan data inflasi.',
        'status' => 'no_filters',
        'title' => 'Inflasi', // Default title
        'bulan' => $request->input('bulan', ''),
        'tahun' => $request->input('tahun', ''),
        'kd_level' => $request->input('kd_level', ''),
        'kd_wilayah' => $request->input('kd_wilayah', ''),
        'kd_komoditas' => $request->input('kd_komoditas', ''),
        'sort' => $request->input('sort', 'kd_komoditas'),
        'direction' => $request->input('direction', 'asc'),
    ];

    // Check if all required filters are present
    if ($request->filled('bulan') && $request->filled('tahun') && $request->filled('kd_level') && $request->filled('kd_wilayah')) {
        $bulanTahun = BulanTahun::where('bulan', $request->input('bulan'))
            ->where('tahun', $request->input('tahun'))
            ->first();

        if (!$bulanTahun) {
            // Case 1: Bulan/Tahun not found
            $response['message'] = 'Tidak ada data tersedia untuk bulan dan tahun yang dipilih.';
            $response['status'] = 'no_data';
            $response['title'] = $this->generateTableTitle($request);
        } else {
            // Build the title
            $title = $this->generateTableTitle($request);

            // Construct the query
            if ($request->input('kd_level') === 'all') {
                $query = Inflasi::select('kd_komoditas')
                    ->selectRaw("
                        MAX(CASE WHEN kd_level = '01' THEN inflasi END) as inflasi_01,
                        MAX(CASE WHEN kd_level = '01' THEN andil END) as andil_01,
                        MAX(CASE WHEN kd_level = '02' THEN inflasi END) as inflasi_02,
                        MAX(CASE WHEN kd_level = '02' THEN andil END) as andil_02,
                        MAX(CASE WHEN kd_level = '03' THEN inflasi END) as inflasi_03,
                        MAX(CASE WHEN kd_level = '03' THEN andil END) as andil_03,
                        MAX(CASE WHEN kd_level = '04' THEN inflasi END) as inflasi_04,
                        MAX(CASE WHEN kd_level = '04' THEN andil END) as andil_04,
                        MAX(CASE WHEN kd_level = '05' THEN inflasi END) as inflasi_05,
                        MAX(CASE WHEN kd_level = '05' THEN andil END) as andil_05
                    ")
                    ->where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                    ->where('kd_wilayah', $request->input('kd_wilayah'))
                    ->groupBy('kd_komoditas')
                    ->with('komoditas');
            } else {
                $query = Inflasi::query()->with('komoditas')
                    ->where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                    ->where('kd_level', $request->input('kd_level'))
                    ->where('kd_wilayah', $request->input('kd_wilayah'));
            }

            if ($request->filled('kd_komoditas')) {
                $query->where('kd_komoditas', $request->input('kd_komoditas'));
            }

            $sortColumn = $request->input('sort', 'kd_komoditas');
            $sortDirection = $request->input('direction', 'asc');
            $query->orderBy($sortColumn, $sortDirection);

            $inflasi = $query->paginate(75);

            if ($inflasi->isEmpty()) {
                // Case 2: Bulan/Tahun exists but no Inflasi data
                $response = [
                    'inflasi' => $inflasi,
                    'message' => 'Tidak ada data tersedia untuk filter tersebut.',
                    'status' => 'no_data',
                    'title' => $title,
                    'bulan' => $request->input('bulan'),
                    'tahun' => $request->input('tahun'),
                    'kd_level' => $request->input('kd_level'),
                    'kd_wilayah' => $request->input('kd_wilayah'),
                    'kd_komoditas' => $request->input('kd_komoditas'),
                    'sort' => $sortColumn,
                    'direction' => $sortDirection,
                ];
            } else {
                // Success case
                $response = [
                    'inflasi' => $inflasi,
                    'message' => 'Data ditemukan.',
                    'status' => 'success',
                    'title' => $title,
                    'bulan' => $request->input('bulan'),
                    'tahun' => $request->input('tahun'),
                    'kd_level' => $request->input('kd_level'),
                    'kd_wilayah' => $request->input('kd_wilayah'),
                    'kd_komoditas' => $request->input('kd_komoditas'),
                    'sort' => $sortColumn,
                    'direction' => $sortDirection,
                ];
            }
        }
    }

    return view('data.edit', $response);
}

/**
 * Generate the table title based on request parameters
 */
private function generateTableTitle(Request $request): string
{
    Log::info('Gen title', $request->all());

    $monthNames = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
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
                $namaWilayah = ucfirst(strtolower($wilayahData->nama_wilayah)); // Capitalize first character
                $wilayah = $kd_level === '01' && strlen($kd_wilayah) > 2
                    ? "Kabupaten/Kota {$namaWilayah}"
                    : "Provinsi {$namaWilayah}";
            }
        }

        // Level Harga
        $levelHarga = $levelHargaMap[$kd_level] ?? '';

        // Month and Year
        $monthName = $monthNames[$bulan] ?? '';

        $title = "Inflasi {$wilayah} {$levelHarga} {$monthName} {$tahun}";
    // }

    Log::info('Generated title', ['title' => $title]);

    return $title;
}

    public function create(): View
    {
//        $wilayah = Wilayah::all();
        return view('data.create');
    }

    public function upload(Request $request)
{
    $request->merge(['bulan' => (int) $request->bulan]);

    $validated = $request->validate([
        'file' => 'required|file|mimes:xlsx,xls',
        'bulan' => 'required|integer|between:1,12',
        'tahun' => 'required|integer|min:2000|max:2100',
        'level' => 'required|string|in:01,02,03,04,05',
    ]);

    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', 300);

    try {
        $import = new DataImport($validated['bulan'], $validated['tahun'], $validated['level']);
        Excel::import($import, $request->file('file'));


        $summary = $import->getSummary();

        // Check for errors first
        if ($import->getErrors()->isNotEmpty()) {
            $failedRow = $summary['failed_row'];
            $chunkSize = 100; // Matches DataImport::chunkSize()
            $lastSuccessfulRow = floor(($failedRow - 1) / $chunkSize) * $chunkSize;

            $errors = $import->getErrors()->all();
            $firstError = $errors[0] ?? '';
            $errorMessages = [];

            if ($failedRow === 2 && str_contains($firstError, 'kd_komoditas kosong')) {
                $errorMessages = [
                    "File mungkin tidak memiliki header yang benar. Perbaiki sesuai template",
                    "Kesalahan ditemukan di baris $failedRow: $firstError",
                ];
            } else {
                $errorMessages = [
                    "Terdapat kesalahan di baris $failedRow",
                    ...$errors,
                    "Upload berhasil sampai dengan baris $lastSuccessfulRow",
                    "Hapus data sampai baris $lastSuccessfulRow (opsional), perbaiki baris selanjutnya.",
                ];

                if ($summary['updated'] > 0 || $summary['inserted'] > 0) {
                    $errorMessages[] = "Data yang berhasil diproses sebelum kesalahan: {$summary['updated']} update (jika ada perubahan), {$summary['inserted']} data baru.";
                }
            }

            return redirect()->back()->withErrors($errorMessages);
        }

        // Handle success case explicitly
        if ($summary['updated'] === 0 && $summary['inserted'] === 0) {
            $messageLines = [
                "Apakah file kosong? Tidak ada data yang berhasil diimpor.",
                "Periksa file Anda dan coba lagi.",
            ];
            return redirect()->back()->withErrors($messageLines);
        }

        $message = "Data berhasil diproses: {$summary['updated']} update, {$summary['inserted']} data baru.";
        return redirect()->back()->with('success', $message);

    } catch (\Exception $e) {
        return redirect()->back()->withErrors(['Error importing data: ' . $e->getMessage()]);
    }
}

public function delete(Request $request, $id)
    {
        // Assuming you have an Inflasi model or similar
        $inflasi = Inflasi::findOrFail($id);

        // Check if delete_rekonsiliasi is true in the request body
        // if ($request->input('delete_rekonsiliasi')) {
        //     // Logic to delete related rekonsiliasi data (adjust based on your schema)
        //     $inflasi->rekonsiliasi()->delete();
        // }

        $inflasi->delete();

        return response()->json(['success' => true], 200);
    }

    public function hapus(Request $request)
    {
        Log::info('Hapus method started', $request->all());

        $request->merge(['bulan' => (int) $request->bulan]);

        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:2100',
            'level' => 'required|string|in:01,02,03,04,05',
        ]);

        try {
            $bulanTahun = BulanTahun::where('bulan', $request->bulan)
                ->where('tahun', $request->tahun)
                ->first();

            if (!$bulanTahun) {
                return redirect()->back()->withErrors(['No data found for the specified period.']);
            }

            $deletedRows = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->where('kd_level', $request->level)
                ->delete();

            if ($deletedRows > 0) {
                return redirect()->back()->with('success', ["Data deleted successfully. Deleted rows: $deletedRows"]);
            } else {
                return redirect()->back()->withErrors(['No matching records found to delete.']);
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['Error deleting data: ' . $e->getMessage()]);
        }
    }


    public function findInflasiId(Request $request)
{
    try {
        // Expect a JSON payload with an array of combinations
        $combinations = $request->json()->all();

        if (!is_array($combinations) || empty($combinations)) {
            return response()->json([
                'error' => 'Invalid or empty combinations array'
            ], 400);
        }

        $results = [];

        foreach ($combinations as $combo) {
            $bulan = $combo['bulan'] ?? null;
            $tahun = $combo['tahun'] ?? null;
            $kd_level = $combo['kd_level'] ?? null;
            $kd_wilayah = $combo['kd_wilayah'] ?? null;
            $kd_komoditas = $combo['kd_komoditas'] ?? null;
            $nama_wilayah = $combo['nama_wilayah'] ?? 'Unknown Wilayah';
            $level_harga = $combo['level_harga'] ?? 'Unknown Level Harga';
            $nama_komoditas = $combo['nama_komoditas'] ?? 'Unknown Komoditas';

            // Validate required parameters
            if (!$bulan || !$tahun || !$kd_level || !$kd_wilayah || !$kd_komoditas) {
                $results[] = [
                    'kd_wilayah' => $kd_wilayah,
                    'kd_komoditas' => $kd_komoditas,
                    'nama_wilayah' => $nama_wilayah,
                    'level_harga' => $level_harga,
                    'nama_komoditas' => $nama_komoditas,
                    'error' => 'Missing required parameters',
                    'inflasi_id' => null
                ];
                continue;
            }

            // Find the BulanTahun record
            $bulanTahun = BulanTahun::where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->first();

            if (!$bulanTahun) {
                $results[] = [
                    'kd_wilayah' => $kd_wilayah,
                    'kd_komoditas' => $kd_komoditas,
                    'nama_wilayah' => $nama_wilayah,
                    'level_harga' => $level_harga,
                    'nama_komoditas' => $nama_komoditas,
                    'message' => 'BulanTahun not found',
                    'inflasi_id' => null
                ];
                continue;
            }

            // Find the Inflasi record
            $inflasi = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->where('kd_level', $kd_level)
                ->where('kd_wilayah', $kd_wilayah)
                ->where('kd_komoditas', $kd_komoditas)
                ->first();

            if ($inflasi) {
                $results[] = [
                    'bulan_tahun_id' => $bulanTahun->bulan_tahun_id,
                    'kd_wilayah' => $kd_wilayah,
                    'kd_komoditas' => $kd_komoditas,
                    'nama_wilayah' => $nama_wilayah,
                    'level_harga' => $level_harga,
                    'nama_komoditas' => $nama_komoditas,
                    'inflasi_id' => $inflasi->inflasi_id,
                    'inflasi' => $inflasi->inflasi
                ];
            } else {
                $results[] = [
                    'bulan_tahun_id' => $bulanTahun->bulan_tahun_id,
                    'kd_wilayah' => $kd_wilayah,
                    'kd_komoditas' => $kd_komoditas,
                    'nama_wilayah' => $nama_wilayah,
                    'level_harga' => $level_harga,
                    'nama_komoditas' => $nama_komoditas,
                    'message' => 'Inflasi record not found',
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
            'message' => $e->getMessage()
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
        // Log incoming data for debugging
        Log::info('Request data:', $request->all());

        $validated = $request->validate([
            'inflasi_ids' => 'required|array',
            'inflasi_ids.*' => 'integer',
            'bulan_tahun_ids' => 'required|array',
            'bulan_tahun_ids.*' => 'integer',
        ]);

        try {
            foreach ($validated['inflasi_ids'] as $index => $inflasi_id) {
                Rekonsiliasi::create([
                    'inflasi_id' => $inflasi_id,
                    'bulan_tahun_id' => $validated['bulan_tahun_ids'][$index],
                    'user_id' => null,
                    'terakhir_diedit' => null,
                    'alasan' => null,
                    'detail' => null,
                    'media' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rekonsiliasi berhasil dikonfirmasi.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Rekonsiliasi failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Rekonsiliasi gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateBulanTahun(Request $request)
    {
        Log::info('Starting updateBulanTahun', [
            'request_data' => $request->all(),
            'timestamp' => now()
        ]);

        // Validate the request
        try {
            $request->validate([
                'bulan' => 'required|string|in:01,02,03,04,05,06,07,08,09,10,11,12',
                'tahun' => 'required|string|digits:4',
            ]);
            Log::info('Request validated successfully', [
                'bulan' => $request->input('bulan'),
                'tahun' => $request->input('tahun')
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'status' => 'fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        // Check if this bulan-tahun combo already exists
        $existing = BulanTahun::where('bulan', ltrim($bulan, '0'))
                            ->where('tahun', $tahun)
                            ->first();
        Log::info('Checked for existing record', [
            'bulan' => ltrim($bulan, '0'),
            'tahun' => $tahun,
            'existing' => $existing ? $existing->toArray() : null
        ]);

        // Fetch the current active record
        $currentActive = BulanTahun::where('aktif', 1)->first();
        Log::info('Fetched current active record', [
            'current_active' => $currentActive ? $currentActive->toArray() : null
        ]);

        if ($existing && $existing->aktif == 1) {
            Log::warning('Attempted to update already active period', [
                'requested_bulan' => $bulan,
                'requested_tahun' => $tahun
            ]);
            return response()->json([
                'status' => 'fail',
                'message' => 'Bulan dan tahun ini sudah aktif',
                'details' => [
                    'requested_bulan' => $bulan,
                    'requested_tahun' => $tahun,
                    'current_active_bulan' => $currentActive ? sprintf('%02d', $currentActive->bulan) : null,
                    'current_active_tahun' => $currentActive ? $currentActive->tahun : null,
                    'hint' => 'Select a different bulan and tahun combination to set a new active period.'
                ]
            ], 422);
        }

        try {
            // Deactivate current active record
            BulanTahun::where('aktif', 1)->update(['aktif' => 0]);
            Log::info('Deactivated current active record');

            if ($existing) {
                $existing->update(['aktif' => 1]);
                Log::info('Updated existing record to active', ['record' => $existing->toArray()]);
            } else {
                $newRecord = BulanTahun::create([
                    'bulan' => ltrim($bulan, '0'),
                    'tahun' => $tahun,
                    'aktif' => 1,
                ]);
                Log::info('Created new active record', ['record' => $newRecord->toArray()]);
            }

            // Clear cache
            Cache::forget('bt_aktif');
            Log::info('Cache cleared for bt_aktif');

            return response()->json([
                'status' => 'success',
                'message' => 'Bulan dan tahun updated successfully',
                'updated' => [
                    'bulan' => $bulan,
                    'tahun' => $tahun
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error during update process', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'bulan' => $bulan,
                'tahun' => $tahun
            ]);
            return response()->json([
                'status' => 'fail',
                'message' => 'Failed to update bulan and tahun',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function pengaturan()
    {
        return view('pengaturan.index');
    }
}
