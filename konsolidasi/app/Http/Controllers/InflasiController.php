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
use App\Exceptions\EarlyHaltException;


use App\Imports\DataImport;
use App\Models\BulanTahun;
use App\Imports\FinalImport;
use App\Models\Wilayah;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class InflasiController extends Controller
{

    /**
     * Render the create/upload view.
     */
    public function create(): View
    {
        return view('data.create');
    }

    /**
     * Delete Inflasi data for a specific period and level.
     */
    public function hapus(Request $request)
    {
        Log::info('Hapus method started', $request->all());

        try {
            $request->merge(['bulan' => (int) $request->bulan]);

            $request->validate([
                'bulan' => 'required|integer|between:1,12',
                'tahun' => 'required|integer|min:2000|max:2100',
                'level' => 'required|string|in:01,02,03,04,05',
            ]);

            $bulanTahun = BulanTahun::where('bulan', $request->bulan)
                ->where('tahun', $request->tahun)
                ->first();

            // title
            $title = "Data " . BulanTahun::getBulanName($request->bulan) . " Tahun {$request->tahun} level " . LevelHarga::getLevelHargaNameComplete($request->level);

            if (!$bulanTahun) {
                Log::warning('BulanTahun not found', ['bulan' => $request->bulan, 'tahun' => $request->tahun]);
                return redirect()->back()->withErrors(["$title gagal dihapus: Periode tidak ditemukan."]);
            }

            //hapus rekon cuz fk? no need bcs cascade on delete
            $deletedRows = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->where('kd_level', $request->level)
                ->delete();

            Cache::forget("rekonsiliasi_aktif");
            Cache::forget("dashboard_data");

            Log::info("Cache cleared & data deleted", [
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
                'level' => $request->level,
                'bulan_tahun_id' => $bulanTahun->bulan_tahun_id,
                'user_id' => auth()->id(),
            ]);

            if ($deletedRows > 0) {
                return redirect()->back()->with('success', "$title berhasil dihapus: {$deletedRows} data dihapus.");
            } else {
                return redirect()->back()->withErrors(["$title gagal dihapus: Tidak ada data untuk dihapus."]);
            }
        } catch (ValidationException  $e) {
            Log::warning('Validation failed in hapus', ['errors' => $e->errors()]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error in hapus', ['message' => $e->getMessage()]);
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
        try {
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

            $bulanTahun = BulanTahun::where('bulan', $validated['bulan'])
                ->where('tahun', $validated['tahun'])
                ->first();

            if (!$bulanTahun) {
                Log::warning('BulanTahun not found for upload', ['bulan' => $validated['bulan'], 'tahun' => $validated['tahun']]);
                return redirect()->back()->withErrors(['bulan' => 'Periode tidak ditemukan untuk bulan dan tahun yang dipilih. Harap aktifkan periode ini terlebih dahulu.']);
            }

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

            Cache::forget("rekonsiliasi_aktif");
            Cache::forget("dashboard_data");

            Log::info("Cache rekonsiliasi_aktif & dashboard_data flushed, data import");

            return $this->handleImportResult($import, $summary, $validated, false); // Pass false for regular import
        } catch (\App\Exceptions\EarlyHaltException $e) {
            Log::info("Import halted successfully: " . $e->getMessage());
            $summary = $import->getSummary();
            return $this->handleImportResult($import, $summary, $validated, false);
        } catch (ValidationException $e) {
            Log::error('Validation gagal', ['errors' => $e->errors()]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Import failed in upload', ['message' => $e->getMessage()]);
            return redirect()->back()->withErrors(['file' => 'Terjadi kegagalan: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle the results of an import process and return a response.
     *
     * Displays a success message with the import summary, including inserted, updated,
     * and optional Rekonsiliasi counts. If errors exist, handles them with a detailed error message.
     *
     * @param mixed $import The import instance (DataImport or FinalImport).
     * @param array $summary The import summary from getSummary().
     * @param array $validated The validated request data.
     * @param bool $isFinalImport Whether this is a final import (affects message and fields).
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleImportResult($import, array $summary, array $validated, bool $isFinalImport = false)
    {
        $errors = $import->getErrors()->all();
        $inserted = $summary['inserted'] ?? 0;
        $updated = $summary['updated'] ?? 0;
        $failedRow = $summary['failed_row'] ?? null;
        $rekonsiliasi_created = $summary['rekonsiliasi_created'] ?? 0;
        $skipped_rekonsiliasi = $summary['skipped_rekonsiliasi'] ?? 0;
        $levelHarga = $validated['level'];
        $bulan = $validated['bulan'];
        $tahun = $validated['tahun'];

        // Title prefix
        $titlePrefix = $isFinalImport ? "Data Final" : "Data";
        $title = "$titlePrefix " . BulanTahun::getBulanName($bulan) . " Tahun {$tahun} level " . LevelHarga::getLevelHargaNameComplete($levelHarga);

        // If there are errors or a failed row, handle them
        if (!empty($errors) || $failedRow !== null) {
            $errorMessage = "$title gagal diimpor";
            $errorMessage .= $failedRow ? " pada baris {$failedRow}" : "";
            $errorMessage .= ": " . (empty($errors) ? "Terjadi kesalahan tak terduga" : implode(', ', $errors)) . ". ";
            if ($isFinalImport) {
                $errorMessage .= "Sebelum kegagalan, {$updated} data final diperbarui.";
            } else {
                $errorMessage .= "Sebelum kegagalan, {$inserted} data baru ditambahkan, {$updated} data diperbarui, {$rekonsiliasi_created} rekonsiliasi dibuat.";
            }
            return redirect()->back()->withErrors(['file' => $errorMessage]);
        }

        // If no data was processed
        if ($inserted === 0 && $updated === 0) {
            return redirect()->back()->withErrors([
                'file' => "$title gagal diimpor: Tidak ada data yang berhasil diimpor. Pastikan file Anda tidak kosong dan formatnya sesuai.",
            ]);
        }

        // Success case
        $message = "$title berhasil diimpor: ";
        if ($isFinalImport) {
            $message .= "{$updated} data final berhasil diperbarui.";
        } else {
            $message .= "{$inserted} data baru ditambahkan, {$updated} data diperbarui, {$rekonsiliasi_created} komoditas rekonsiliasi ditambahkan";
            if ($skipped_rekonsiliasi > 0) {
                $message .= ", {$skipped_rekonsiliasi} komoditas rekonsiliasi dilewati karena rekonsiliasi di level nasional, rekonsiliasi di kabupaten/kota untuk level harga selain HK, atau sudah ada di sistem";
            }
            $message .= ".";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Render the finalization view.
     */

    public function finalisasi()
    {
        return view('data.finalisasi');
    }

    /**
     * Handles the upload of an Excel file for final inflation data import.
     *
     */
    public function final_upload(Request $request)
    {
        Log::debug('Final upload request received', [
            'request' => $request->all(),
            'has_file' => $request->hasFile('file'),
            'file' => $request->file('file') ? $request->file('file')->getClientOriginalName() : 'No file',
        ]);

        try {
            $rules = [
                'file' => 'required|file|mimes:xlsx,xls|max:5120', // 5MB
                'bulan' => 'required|integer|between:1,12',
                'tahun' => 'required|integer|min:2000|max:3000',
                'level' => 'required|string|in:01,02,03,04,05',
            ];

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

            $validated = $request->validate($rules, $messages);

            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', 300);

            $bulanTahun = BulanTahun::where('bulan', $validated['bulan'])
                ->where('tahun', $validated['tahun'])
                ->first();
            if (!$bulanTahun) {
                Log::warning('Bulan tidak ditemukan untuk final_upload', ['bulan' => $validated['bulan'], 'tahun' => $validated['tahun']]);
                return redirect()->back()->withErrors(['bulan' => 'Periode tidak ditemukan untuk bulan dan tahun yang dipilih.']);
            }

            if (!$request->file('file')->isValid()) {
                Log::error('File upload failed', [
                    'error' => $request->file('file')->getErrorMessage(),
                ]);
                return redirect()->back()->withErrors(['file' => 'File gagal diunggah: ' . $request->file('file')->getErrorMessage()])->withInput();
            }

            $import = new FinalImport($validated['bulan'], $validated['tahun'], $validated['level']);
            Excel::import($import, $request->file('file'));
            $summary = $import->getSummary();

            return $this->handleImportResult($import, $summary, $validated, true); // Pass true for final import
        } catch (\App\Exceptions\EarlyHaltException $e) {
            Log::info("Import halted successfully: " . $e->getMessage());
            $summary = $import->getSummary();
            return $this->handleImportResult($import, $summary, $validated, true);
        } catch (ValidationException $e) {
            Log::error('Validation failed in final_upload', ['errors' => $e->errors()]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Import failed in final_upload', ['message' => $e->getMessage()]);
            return redirect()->back()->withErrors(['file' => 'Error importing data: ' . $e->getMessage()]);
        }
    }

    /**
     * Update an Inflasi record.
     */
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
                    'data' => null
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

            Cache::forget("rekonsiliasi_aktif");
            Cache::forget("dashboard_data");

            Log::info("Cache rekonsiliasi_aktif & dashboard_data flushed, data import");

            return response()->json([
                'message' => 'Data inflasi berhasil diperbarui',
                'data' => null
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Data inflasi tidak ditemukan.',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in update', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui data: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Export report to Excel.
     */

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
    public function fetchEditData(Request $request)
    {
        // Fallback to active BulanTahun if bulan or tahun is missing
        try {
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
                'kd_wilayah' => 'required|integer|max:9999|exists:wilayah,kd_wilayah',
                'kd_komoditas' => 'nullable|integer|max:99|exists:komoditas,kd_komoditas',
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

                $inflasi = $query->get();
            } else {
                // Specific kd_level
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

                if (!is_null($kd_komoditas) && $kd_komoditas !== '') {
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
                    ],
                ], 200);
            }

            // Transform data based on kd_level
            $response['data']['inflasi'] = $kd_level === '00'
                ? InflasiAllLevelResource::collection($inflasi)
                : InflasiResource::collection($inflasi);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Handle unexpected errors
            Log::error('Error in fetchEditData', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memuat data: ' . $e->getMessage(),
                'data' => ['inflasi' => [], 'title' => 'Inflasi']
            ], 500);
        }
    }

    /**
     * Generate the table title based on request parameters
     */
    private function generateTableTitle(Request $request): string
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error in generateTableTitle', ['message' => $e->getMessage()]);
            return 'Inflasi';
        }
    }


    /**
     * Delete a single Inflasi record.
     */
    public function delete($id)
    {
        try {
            // Find the Inflasi record or fail with a 404
            $inflasi = Inflasi::findOrFail($id);
            $inflasi->delete();

            Cache::forget("rekonsiliasi_aktif");
            Cache::forget("dashboard_data");

            Log::info("Cache rekonsiliasi_aktif & dashboard_data flushed, delete single inflasi");

            return response()->json([
                'message' => 'Data inflasi berhasil dihapus.',
                'data' => null
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Data inflasi tidak ditemukan.',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in delete', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menghapus data inflasi: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function findInflasiId(Request $request)
    {
        try {
            // Validate input
            $combinations = $request->json()->all();

            if (!is_array($combinations) || empty($combinations)) {
                return response()->json([
                    'message' => 'Harap masukkan kombinasi data yang valid.',
                    'data' => []
                ], 400);
            }

            $results = [];
            $allFound = true;

            // Process each combination
            foreach ($combinations as $combo) {
                $bulan = $combo['bulan'] ?? null;
                $tahun = $combo['tahun'] ?? null;
                $kd_level = $combo['kd_level'] ?? null;
                $kd_wilayah = $combo['kd_wilayah'] ?? null;
                $kd_komoditas = $combo['kd_komoditas'] ?? null;

                // Get names from model methods
                $level_harga = LevelHarga::getLevelHargaNameComplete($kd_level) ?? 'Unknown Level Harga';
                $nama_wilayah = Wilayah::getWilayahName($kd_wilayah) ?? 'Unknown Wilayah';
                $nama_komoditas = Komoditas::getKomoditasName($kd_komoditas) ?? $combo['nama_komoditas'] ?? 'Unknown Komoditas';

                // Check for missing parameters (explicitly allow "0" as valid)
                if (
                    !isset($bulan) || $bulan === '' || !isset($tahun) || $tahun === '' ||
                    !isset($kd_level) || $kd_level === '' || !isset($kd_wilayah) || $kd_wilayah === '' ||
                    !isset($kd_komoditas) || $kd_komoditas === ''
                ) {
                    $allFound = false;
                    return response()->json([
                        'message' => "Inflasi level harga {$level_harga} wilayah {$nama_wilayah} komoditas {$nama_komoditas} tidak ditemukan.",
                        'data' => []
                    ], 404);
                }

                // Find BulanTahun record
                $bulanTahun = BulanTahun::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->first();

                if (!$bulanTahun) {
                    $allFound = false;
                    return response()->json([
                        'message' => "Inflasi level harga {$level_harga} wilayah {$nama_wilayah} komoditas {$nama_komoditas} tidak ditemukan.",
                        'data' => []
                    ], 404);
                }

                // Find Inflasi record
                $inflasi = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                    ->where('kd_level', $kd_level)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->first();

                if (!$inflasi) {
                    $allFound = false;
                    return response()->json([
                        'message' => "Inflasi level harga {$level_harga} wilayah {$nama_wilayah} komoditas {$nama_komoditas} tidak ditemukan.",
                        'data' => []
                    ], 404);
                }

                // Prepare data for resource
                $inflasi->nama_komoditas = $nama_komoditas; // Add nama_komoditas to model for resource
                $results[] = new InflasiResource($inflasi);
            }

            // If all combinations are found, return success
            if ($allFound) {
                return response()->json([
                    'message' => 'Data inflasi berhasil ditemukan.',
                    'data' => $results
                ], 200);
            }

            // Fallback (shouldn't reach here due to early returns)
            return response()->json([
                'message' => 'Beberapa data inflasi tidak ditemukan.',
                'data' => []
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in findInflasiId: ' . $e->getMessage(), [
                'combinations' => $request->json()->all()
            ]);
            return response()->json([
                'message' => 'Terjadi kesalahan server. Silakan coba lagi nanti.',
                'data' => []
            ], 500);
        }
    }
}
