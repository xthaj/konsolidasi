<?php

namespace App\Http\Controllers;

use App\Exports\InflasiExport;
use App\Http\Resources\InflasiAllLevelResource;
use App\Http\Resources\InflasiResource;
use App\Models\Komoditas;
use App\Models\LevelHarga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Inflasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DataImport;
use App\Models\BulanTahun;
use App\Imports\FinalImport;
use App\Models\Wilayah;

// InflasiAllLevelResource
// InflasiResource
// Wilayah




class InflasiController extends Controller
{
    // views

    public function create(): View
    {
        return view('data.create');
    }

    public function finalisasi()
    {
        return view('data.finalisasi');
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
            'file' => 'required|file|mimes:xlsx|max:5120', // 5MB
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:3000',
            'level' => 'required|string|in:01,02,03,04,05',
        ];

        // Custom validation messages
        $messages = [
            'file.required' => 'File Excel wajib diunggah.',
            'file.file' => 'File harus berupa dokumen.',
            'file.mimes' => 'Format file harus xlsx.',
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

    public function update(Request $request, $id)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'nilai_inflasi' => 'required|numeric',
                'andil' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            DB::beginTransaction();

            // Find the Inflasi record
            $inflasi = Inflasi::findOrFail($id);

            // Prepare data to update
            $updateData = [
                'nilai_inflasi' => $request->nilai_inflasi,
            ];

            // Include andil only if provided and kd_wilayah is '0'
            if ($request->has('andil') && $inflasi->kd_wilayah === '0') {
                $updateData['andil'] = $request->andil;
            }

            // Update the record
            $inflasi->update($updateData);

            DB::commit();

            return response()->json([
                'message' => 'Data inflasi berhasil diperbarui',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Data inflasi tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui data: ' . $e->getMessage(),
            ], 500);
        }
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

    // the view guy
    public function edit(Request $request): View
    {
        return view('data.edit');
    }

    // the api caller
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

    // singles

    public function delete($id)
    {
        try {
            // Find the Inflasi record or fail with a 404
            $inflasi = Inflasi::findOrFail($id);

            // Delete the Inflasi record
            $inflasi->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Data inflasi berhasil dihapus.',
                'data' => null,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle case where Inflasi record is not found
            return response()->json([
                'status' => 'error',
                'message' => 'Data inflasi tidak ditemukan.',
                'data' => null,
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data inflasi. Silakan coba lagi.',
                'data' => null,
            ], 500);
        }
    }
}
