<?php

namespace App\Http\Controllers;

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



class InflasiController extends Controller
{
    public function create(): View
    {
        return view('data.create');
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
}
