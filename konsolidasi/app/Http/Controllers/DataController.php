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
use App\Imports\FinalImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Rekonsiliasi;
use Illuminate\Support\Facades\DB;
use App\Exports\InflasiExport;

class DataController extends Controller
{
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
        Log::info('Upload Request Data:', $request->all());
        Log::info('Uploaded File:', [$request->hasFile('file'), $request->file('file')]);

        $request->merge(['bulan' => (int) $request->bulan]);

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer',
            'level' => 'required|string|in:01,02,03,04,05',
        ]);

        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        try {
            $import = new DataImport($validated['bulan'], $validated['tahun'], $validated['level']);
            Excel::import($import, $request->file('file'));

            $summary = $import->getSummary();

            // Map level harga to human-readable format
            $levelHargaMap = [
                '01' => 'Harga Konsumen Kota',
                '02' => 'Harga Konsumen Desa',
                '03' => 'Harga Perdagangan Besar',
                '04' => 'Harga Produsen Desa',
                '05' => 'Harga Produsen',
            ];
            $levelHarga = $levelHargaMap[$validated['level']] ?? 'Unknown';

            // Map bulan to Indonesian month names
            $bulanMap = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember'
            ];
            $bulan = $bulanMap[$validated['bulan']] ?? $validated['bulan'];

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
                $errorMessages = [
                    "Apakah file kosong? Tidak ada data yang berhasil diimpor.",
                    "Periksa file Anda dan coba lagi.",
                ];
                return redirect()->back()->withErrors($errorMessages);
            }

            // Success message with level harga, bulan, and tahun
            $message = [
                "Data Level Harga $levelHarga bulan $bulan Tahun {$validated['tahun']} berhasil diupload.",
                "Data berhasil diproses: {$summary['updated']} update, {$summary['inserted']} data baru.",
            ];
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['Error importing data: ' . $e->getMessage()]);
        }
    }

    public function final_upload(Request $request)
    {
        Log::info('Upload Request Data:', $request->all());
        Log::info('Uploaded File:', [$request->hasFile('file'), $request->file('file')]);

        $request->merge(['bulan' => (int) $request->bulan]);

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer',
            'level' => 'required|string|in:01,02,03,04,05',
        ]);

        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $response = [
            'success' => false,
            'message' => [],
            'data' => [],
        ];

        try {
            $import = new FinalImport($validated['bulan'], $validated['tahun'], $validated['level']);
            Excel::import($import, $request->file('file'));
            $summary = $import->getSummary();

            if ($import->getErrors()->isNotEmpty()) {
                $failedRow = $summary['failed_row'];
                $chunkSize = 100;
                $lastSuccessfulRow = floor(($failedRow - 1) / $chunkSize) * $chunkSize;
                $errors = $import->getErrors()->all();
                $firstError = $errors[0] ?? '';

                $response['message'] = $failedRow === 2 && str_contains($firstError, 'kd_komoditas kosong')
                    ? [
                        "File mungkin tidak memiliki header yang benar. Perbaiki sesuai template",
                        "Kesalahan ditemukan di baris $failedRow: $firstError",
                    ]
                    : [
                        "Terdapat kesalahan di baris $failedRow",
                        ...$errors,
                        "Upload berhasil sampai dengan baris $lastSuccessfulRow",
                        "Hapus data sampai baris $lastSuccessfulRow (opsional), perbaiki baris selanjutnya.",
                        ...($summary['updated'] > 0 || $summary['inserted'] > 0
                            ? ["Data yang berhasil diproses sebelum kesalahan: {$summary['updated']} update (jika ada perubahan), {$summary['inserted']} data baru."]
                            : []),
                    ];
                $response['data'] = [
                    'failed_row' => $failedRow,
                    'last_successful_row' => $lastSuccessfulRow,
                    'updated' => $summary['updated'],
                    'inserted' => $summary['inserted'],
                ];
            } elseif ($summary['updated'] === 0 && $summary['inserted'] === 0) {
                $response['message'] = [
                    "Apakah file kosong? Tidak ada data yang berhasil diimpor.",
                    "Periksa file Anda dan coba lagi.",
                ];
            } else {
                $response['success'] = true;
                $response['message'] = ["Data berhasil diproses: {$summary['updated']} update, {$summary['inserted']} data baru."];
                $response['data'] = [
                    'updated' => $summary['updated'],
                    'inserted' => $summary['inserted'],
                ];
            }
        } catch (\Exception $e) {
            $response['message'] = ["Error importing data: {$e->getMessage()}"];
        }

        if ($request->wantsJson()) {
            return response()->json($response);
        }

        return redirect()->back()->with('response', $response);
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
                return redirect()->back()->withErrors(['Tidak ada data tersedia untuk periode tersebut.']);
            }

            //hapus rekon cuz fk? no need bcs cascade on delete
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
            DB::beginTransaction();

            // Get existing Rekonsiliasi records with inflasi_id and bulan_tahun_id
            $existingRecords = Rekonsiliasi::whereIn('inflasi_id', $validated['inflasi_ids'])
                ->whereIn('bulan_tahun_id', $validated['bulan_tahun_ids'])
                ->select('inflasi_id', 'bulan_tahun_id')
                ->get()
                ->groupBy('bulan_tahun_id')
                ->mapWithKeys(function ($group, $bulan_tahun_id) {
                    return [$bulan_tahun_id => $group->pluck('inflasi_id')->toArray()];
                })
                ->toArray();

            // Prepare duplicates array with detailed info
            $duplicates = [];
            $createdCount = 0;
            $inputPairs = array_combine($validated['inflasi_ids'], $validated['bulan_tahun_ids']);

            // Fetch wilayah and komoditas details for duplicates
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
                // Check if this combination exists
                if (
                    isset($existingRecords[$bulan_tahun_id]) &&
                    in_array($inflasi_id, $existingRecords[$bulan_tahun_id])
                ) {
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
                    'user_id' => null,
                    'terakhir_diedit' => null,
                    'alasan' => null,
                    'detail' => null,
                    'media' => null,
                ]);
                $createdCount++;
            }

            DB::commit();

            if (!empty($duplicates)) {
                return response()->json([
                    'success' => true,
                    'partial_success' => true,
                    'message' => "Rekonsiliasi berhasil untuk {$createdCount} entri. " .
                        count($duplicates) . " entri dilewati karena sudah termasuk komoditas rekonsilasi.",
                    'duplicates' => $duplicates,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rekonsiliasi berhasil dikonfirmasi untuk semua entri.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rekonsiliasi failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Rekonsiliasi gagal: ' . $e->getMessage(),
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
                'tahun' => 'required|digits:4',
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
                'message' => 'Bulan dan tahun berhasil diperbarui',
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

    public function finalisasi()
    {
        return view('data.finalisasi');
    }

    public function pengaturan()
    {
        return view('pengaturan.index');
    }

    // Masters

    public function master_komoditas()
    {
        return view('master.komoditas');
    }

    public function master_wilayah()
    {
        return view('master.wilayah');
    }

    public function master_alasan()
    {
        return view('master.alasan');
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

            $fileName = "inflasi_{$validated['bulan']}_{$validated['tahun']}_level{$validated['level']}.xlsx";
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
