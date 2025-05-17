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

    // gantinya edit
    public function apiEdit(Request $request)
    {
        // Check if 'bulan' or 'tahun' is missing in the request; if so, use the active BulanTahun record
        // This ensures a fallback to the active period if the user doesn't provide these parameters
        if (!$request->filled('bulan') || !$request->filled('tahun')) {
            // Retrieve the active BulanTahun record (where 'aktif' = 1)
            $aktifBulanTahun = BulanTahun::where('aktif', 1)->first();
            // If an active record exists, merge its 'bulan' and 'tahun' into the request
            if ($aktifBulanTahun) {
                $request->merge([
                    'bulan' => $aktifBulanTahun->bulan,
                    'tahun' => $aktifBulanTahun->tahun,
                ]);
            }
        }

        // Extract request parameters, providing defaults where applicable
        // 'bulan' and 'tahun' are required (set above if missing)
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        // Default 'kd_level' to '01' (Harga Konsumen Kota) if not provided
        $kd_level = $request->input('kd_level', '01');
        // Default 'kd_wilayah' to '0' (national level) if not provided
        $kd_wilayah = $request->input('kd_wilayah', 0);
        // 'kd_komoditas' is optional; defaults to empty string
        $kd_komoditas = $request->input('kd_komoditas', '');
        // Default sorting column to 'kd_komoditas' if not specified
        $sortColumn = $request->input('sort', 'kd_komoditas');
        // Default sorting direction to 'asc' if not specified
        $sortDirection = $request->input('direction', 'asc');
        // Merge default values back into the request for consistency
        $request->merge([
            'kd_level' => $kd_level,
            'kd_wilayah' => $kd_wilayah,
        ]);

        // Initialize the response structure with default values
        // Includes status, message, data (inflasi and title), and metadata
        $response = [
            'status' => 'success',
            'message' => 'Data ditemukan.',
            'data' => [
                'inflasi' => [], // Initially empty; populated based on query results
                'title' => $this->generateTableTitle($request), // Generate a title based on request parameters
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(), // Current timestamp in ISO 8601 format
                'api_version' => '1.0', // API version for reference
            ],
        ];

        // Fetch the BulanTahun record matching the provided 'bulan' and 'tahun'
        $bulanTahun = BulanTahun::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        // Case: No BulanTahun record found for the given bulan and tahun
        // Return a 'no_data' response with an empty inflasi array
        if (!$bulanTahun) {
            $response['status'] = 'no_data';
            $response['message'] = 'Tidak ada data tersedia untuk bulan dan tahun yang dipilih.';
            $response['data']['inflasi'] = [];
            return response()->json($response);
        }

        // Case: kd_level is '00' (retrieve data for all price levels)
        if ($kd_level === '00') {
            // Build a query to join Komoditas and Inflasi tables
            $query = Komoditas::query()
                ->leftJoin('inflasi', function ($join) use ($bulanTahun, $kd_wilayah) {
                    // Join conditions: match komoditas, bulan_tahun_id, and kd_wilayah
                    $join->on('komoditas.kd_komoditas', '=', 'inflasi.kd_komoditas')
                        ->where('inflasi.bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                        ->where('inflasi.kd_wilayah', $kd_wilayah);
                })
                // Select komoditas fields
                ->select('komoditas.kd_komoditas', 'komoditas.nama_komoditas')
                // Use MAX and CASE to pivot inflasi data for each kd_level (01 to 05)
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
                // Group by komoditas to aggregate data
                ->groupBy('komoditas.kd_komoditas', 'komoditas.nama_komoditas');

            // If a specific komoditas is provided, filter by it
            if ($kd_komoditas) {
                $query->where('komoditas.kd_komoditas', $kd_komoditas);
            }

            // Apply sorting based on request parameters
            $query->orderBy($sortColumn, $sortDirection);
            // Execute the query
            $inflasi = $query->get();
        } else {
            // Case: Specific kd_level (e.g., '01', '02', etc.)
            // Build a query for a specific price level
            $query = Komoditas::query()
                ->leftJoin('inflasi', function ($join) use ($bulanTahun, $kd_level, $kd_wilayah) {
                    // Join conditions: match komoditas, bulan_tahun_id, kd_level, and kd_wilayah
                    $join->on('komoditas.kd_komoditas', '=', 'inflasi.kd_komoditas')
                        ->where('inflasi.bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                        ->where('inflasi.kd_level', $kd_level)
                        ->where('inflasi.kd_wilayah', $kd_wilayah);
                })
                // Select specific fields from both tables
                ->select(
                    'komoditas.kd_komoditas',
                    'komoditas.nama_komoditas',
                    'inflasi.inflasi_id',
                    'inflasi.nilai_inflasi',
                    'inflasi.andil',
                    'inflasi.kd_wilayah'
                );

            // If a specific komoditas is provided, filter by it
            if ($kd_komoditas) {
                $query->where('komoditas.kd_komoditas', $kd_komoditas);
            }

            // Apply sorting based on request parameters
            $query->orderBy($sortColumn, $sortDirection);
            // Execute the query
            $inflasi = $query->get();
        }

        // Case: No data found
        if ($inflasi->isEmpty()) {
            $response['status'] = 'no_data';
            $response['message'] = 'Tidak ada data inflasi tersedia untuk filter tersebut.';
            $response['data']['inflasi'] = [];
        }
        // Case: Data found for kd_level '00' (all price levels)
        elseif ($kd_level === '00') {
            $response['status'] = 'success';
            $response['message'] = 'Data ditemukan.';
            // Transform data using InflasiAllLevelResource for all price levels
            $response['data']['inflasi'] = InflasiAllLevelResource::collection($inflasi);
        }
        // Case: Data found for a specific kd_level
        else {
            $response['status'] = 'success';
            $response['message'] = 'Data ditemukan.';
            // Transform data using InflasiResource for a specific price level
            $response['data']['inflasi'] = InflasiResource::collection($inflasi);
        }

        // Return the JSON response
        return response()->json($response);
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

    public function create(): View
    {
        return view('data.create');
    }

    public function upload(Request $request)
    {
        // Log the entire request for debugging
        Log::debug('Upload request received', [
            'request' => $request->all(),
            'has_file' => $request->hasFile('file'),
            'file' => $request->file('file') ? $request->file('file')->getClientOriginalName() : 'No file',
        ]);

        // Validation rules
        $rules = [
            'file' => 'required|file|mimes:xlsx,xls|max:5120', // 5MB
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:3000',
            'level' => 'required|string|in:01,02,03,04,05',
        ];

        // Custom validation messages
        $messages = [
            'file.required' => 'File Excel wajib diunggah.',
            'file.file' => 'File harus berupa dokumen.',
            'file.mimes' => 'Format file harus xlsx atau xls.',
            'file.max' => 'Ukuran file tidak boleh lebih dari 5MB.',
            'bulan.required' => 'Bulan wajib diisi.',
            'bulan.integer' => 'Bulan harus berupa angka.',
            'bulan.between' => 'Bulan tidak valid.',
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.integer' => 'Tahun harus berupa angka.',
            'tahun.min' => 'Tahun tidak valid.',
            'tahun.max' => 'Tahun tidak valid.',
            'level.required' => 'Level harga wajib dipilih.',
            'level.string' => 'Level harga tidak valid.',
            'level.in' => 'Level harga harus salah satu dari: 01, 02, 03, 04, atau 05.',
        ];

        // Validate request
        try {
            $validated = $request->validate($rules, $messages);
        } catch (ValidationException $e) {
            // Log validation errors
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);

            // Return back with errors
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        // Set PHP runtime limits
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        try {
            // Verify file is valid
            if (!$request->file('file')->isValid()) {
                Log::error('File upload failed', [
                    'error' => $request->file('file')->getErrorMessage(),
                ]);
                return redirect()->back()->withErrors(['file' => 'File gagal diunggah: ' . $request->file('file')->getErrorMessage()])->withInput();
            }

            // Perform import
            $import = new DataImport($validated['bulan'], $validated['tahun'], $validated['level']);
            Excel::import($import, $request->file('file'));
            $summary = $import->getSummary();

            // Handle import results
            return $this->handleImportResult($import, $summary, $validated);
        } catch (\Exception $e) {
            // Log import errors
            Log::error('Import failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors(['file' => 'Error importing data: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle the results of the import process and return a response.
     *
     * Displays a success message with the import summary, including inserted, updated,
     * created, and skipped Rekonsiliasi counts. If errors exist, calls handleImportErrors.
     *
     * @param DataImport $import The import instance.
     * @param array $summary The import summary from getSummary().
     * @param \Illuminate\Support\MessageBag $errors The import errors from getErrors().
     * @param array $validated The validated request data.
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleImportResult(DataImport $import, array $summary, array $validated)
    {
        $errors = $import->getErrors()->all();
        $inserted = $summary['inserted'];
        $updated = $summary['updated'];
        $failedRow = $summary['failed_row'];
        $levelHarga = $validated['level'];
        $bulan = $validated['bulan'];
        $tahun = $validated['tahun'];
        $rekonsiliasi_created = $summary['rekonsiliasi_created']; // Added Rekonsiliasi count
        $skipped_rekonsiliasi = $summary['skipped_rekonsiliasi'] ?? 0; // Extract skipped count

        // Log the summary for debugging
        Log::debug('Import summary', [
            'inserted' => $inserted,
            'updated' => $updated,
            'failed_row' => $failedRow,
            'errors' => $errors,
            'rekonsiliasi_created' => $rekonsiliasi_created,
            'skipped_rekonsiliasi' => $skipped_rekonsiliasi,
        ]);

        // Title prefix
        $title = "Data " . BulanTahun::getBulanName($bulan) . " Tahun {$tahun} level " . LevelHarga::getLevelHargaNameComplete($levelHarga);

        // If there are errors, handle them
        if (!empty($errors) || $failedRow !== null) {
            return $this->handleImportErrors($import, $summary, $levelHarga, $bulan, $tahun);
        }

        // If no data was processed
        if ($inserted === 0 && $updated === 0) {
            return redirect()->back()->withErrors([
                'file' => "$title gagal diimpor: Tidak ada data yang berhasil diimpor. Pastikan file Anda tidak kosong dan formatnya sesuai.",
            ]);
        }

        // Success case
        $message = "$title berhasil diimpor: {$inserted} data baru ditambahkan, {$updated} data diperbarui, {$rekonsiliasi_created} komoditas rekonsiliasi ditambahkan";
        // Append skipped Rekonsiliasi message
        if ($skipped_rekonsiliasi > 0) {
            $message .= ", {$skipped_rekonsiliasi} komoditas rekonsiliasi di-skip karena rekonsiliasi di level nasional, atau rekonsiliasi di kabupaten/kota untuk level harga selain HK";
        }
        $message .= "."; // Ensure consistent punctuation
        return redirect()->back()->with('success', $message);
    }

    /**
     * Handle errors during import.
     *
     * @param DataImport $import
     * @param array $summary
     * @param string $levelHarga
     * @param string $bulan
     * @param int $tahun
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleImportErrors(DataImport $import, array $summary, string $levelHarga, string $bulan, int $tahun)
    {
        $errors = $import->getErrors()->all();
        $inserted = $summary['inserted'];
        $updated = $summary['updated'];
        $rekonsiliasi_created = $summary['rekonsiliasi_created'];
        $failedRow = $summary['failed_row'];

        // Title prefix for the error message
        $title = "Data " . BulanTahun::getBulanName($bulan) . " Tahun {$tahun} level " . LevelHarga::getLevelHargaNameComplete($levelHarga);

        // Build error message
        $errorMessage = "$title gagal diimpor pada baris {$failedRow}: " . implode(', ', $errors) . ". ";
        $errorMessage .= "Sebelum kegagalan, {$inserted} data baru ditambahkan, {$updated} data diperbarui, {$rekonsiliasi_created} rekonsiliasi dibuat.";

        return redirect()->back()->withErrors(['file' => $errorMessage]);
    }

    /**
     * Handles the upload of an Excel file for final inflation data import.
     *
     * Validates the request, processes the file using FinalImport, and returns
     * a response with the import results or errors.
     *
     * @param Request $request The HTTP request containing the file and parameters.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function final_upload(Request $request)
    {
        // Log the entire request for debugging
        Log::debug('Final upload request received', [
            'request' => $request->all(),
            'has_file' => $request->hasFile('file'),
            'file' => $request->file('file') ? $request->file('file')->getClientOriginalName() : 'No file',
        ]);

        // Validation rules
        $rules = [
            'file' => 'required|file|mimes:xlsx,xls|max:5120', // 5MB
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:3000',
            'level' => 'required|string|in:01,02,03,04,05',
        ];

        // Custom validation messages
        $messages = [
            'file.required' => 'File Excel wajib diunggah.',
            'file.file' => 'File harus berupa dokumen.',
            'file.mimes' => 'Format file harus xlsx atau xls.',
            'file.max' => 'Ukuran file tidak boleh lebih dari 5MB.',
            'bulan.required' => 'Bulan wajib diisi.',
            'bulan.integer' => 'Bulan harus berupa angka.',
            'bulan.between' => 'Bulan tidak valid.',
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.integer' => 'Tahun harus berupa angka.',
            'tahun.min' => 'Tahun tidak valid.',
            'tahun.max' => 'Tahun tidak valid.',
            'level.required' => 'Level harga wajib dipilih.',
            'level.string' => 'Level harga tidak valid.',
            'level.in' => 'Level harga harus salah satu dari: 01, 02, 03, 04, atau 05.',
        ];

        // Validate request
        try {
            $validated = $request->validate($rules, $messages);
        } catch (ValidationException $e) {
            // Log validation errors
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);

            // Return back with errors
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        // Set PHP runtime limits
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        try {
            // Verify file is valid
            if (!$request->file('file')->isValid()) {
                Log::error('File upload failed', [
                    'error' => $request->file('file')->getErrorMessage(),
                ]);
                return redirect()->back()->withErrors(['file' => 'File gagal diunggah: ' . $request->file('file')->getErrorMessage()])->withInput();
            }

            // Perform import
            $import = new FinalImport($validated['bulan'], $validated['tahun'], $validated['level']);
            Excel::import($import, $request->file('file'));
            $summary = $import->getSummary();

            // Handle import results
            return $this->handleFinalImportResult($import, $summary, $validated);
        } catch (\Exception $e) {
            // Log import errors
            Log::error('Import failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors(['file' => 'Error importing data: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle the results of the final import process and return a response.
     *
     * Displays a success message with the import summary, including inserted and updated counts.
     * If errors exist, calls handleFinalImportErrors.
     *
     * @param FinalImport $import The import instance.
     * @param array $summary The import summary from getSummary().
     * @param array $validated The validated request data.
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleFinalImportResult(FinalImport $import, array $summary, array $validated)
    {
        $errors = $import->getErrors()->all();
        $inserted = $summary['inserted'];
        $updated = $summary['updated'];
        $failedRow = $summary['failed_row'];
        $levelHarga = $validated['level'];
        $bulan = $validated['bulan'];
        $tahun = $validated['tahun'];

        // Log the summary for debugging
        Log::debug('Final import summary', [
            'inserted' => $inserted,
            'updated' => $updated,
            'failed_row' => $failedRow,
            'errors' => $errors,
        ]);

        // Title prefix
        $title = "Data Final " . BulanTahun::getBulanName($bulan) . " Tahun {$tahun} level " . LevelHarga::getLevelHargaNameComplete($levelHarga);

        // If there are errors, handle them
        if (!empty($errors) || $failedRow !== null) {
            return $this->handleFinalImportErrors($import, $summary, $levelHarga, $bulan, $tahun);
        }

        // If no data was processed
        if ($inserted === 0 && $updated === 0) {
            return redirect()->back()->withErrors([
                'file' => "$title gagal diimpor: Tidak ada data yang berhasil diimpor. Pastikan file Anda tidak kosong dan formatnya sesuai.",
            ]);
        }

        // Success case
        $message = "$title berhasil diimpor: {$inserted} data final baru ditambahkan, {$updated} data final diperbarui.";
        return redirect()->back()->with('success', $message);
    }

    /**
     * Handle errors during final import.
     *
     * Builds an error message with details about the failed row and any processed data.
     *
     * @param FinalImport $import The import instance.
     * @param array $summary The import summary.
     * @param string $levelHarga The level harga code.
     * @param int $bulan The month.
     * @param int $tahun The year.
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleFinalImportErrors(FinalImport $import, array $summary, string $levelHarga, int $bulan, int $tahun)
    {
        $errors = $import->getErrors()->all();
        $inserted = $summary['inserted'];
        $updated = $summary['updated'];
        $failedRow = $summary['failed_row'];

        // Title prefix for the error message
        $title = "Data Final " . BulanTahun::getBulanName($bulan) . " Tahun {$tahun} level " . LevelHarga::getLevelHargaNameComplete($levelHarga);

        // Build error message
        $errorMessage = "$title gagal diimpor pada baris {$failedRow}: " . implode(', ', $errors) . ". ";
        $errorMessage .= "Sebelum kegagalan, {$inserted} data baru ditambahkan, {$updated} data diperbarui.";

        return redirect()->back()->withErrors(['file' => $errorMessage]);
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

    /**
     * Delete Inflasi data for a specific period and level.
     *
     * Validates the request, finds the BulanTahun record, and deletes Inflasi records
     * matching the bulan_tahun_id and kd_level. Rekonsiliasi records are automatically
     * deleted via cascade. Returns a success message with the number of deleted rows or
     * an error message, including a title for the period and level.
     *
     * @param Request $request The HTTP request containing bulan, tahun, and level.
     * @return \Illuminate\Http\RedirectResponse
     */
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

            // title
            $title = "Data " . BulanTahun::getBulanName($request->bulan) . " Tahun {$request->tahun} level " . LevelHarga::getLevelHargaNameComplete($request->level);

            if (!$bulanTahun) {
                return redirect()->back()->withErrors(["$title gagal dihapus: Tidak ada data tersedia untuk periode tersebut."]);
            }

            //hapus rekon cuz fk? no need bcs cascade on delete
            $deletedRows = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->where('kd_level', $request->level)
                ->delete();

            if ($deletedRows > 0) {
                return redirect()->back()->with('success', "$title berhasil dihapus: {$deletedRows} data dihapus.");
            } else {
                return redirect()->back()->withErrors(["$title gagal dihapus: Tidak ada data untuk dihapus."]);
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(["$title gagal dihapus: Error menghapus data: {$e->getMessage()}"]);
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
        } catch (ValidationException $e) {
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
