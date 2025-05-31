<?php

namespace App\Imports;

use App\Models\BulanTahun;
use App\Models\Inflasi;
use App\Models\Komoditas;
use App\Models\Rekonsiliasi;
use App\Models\Wilayah;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Exception;

/**
 * Imports Excel data into the Inflasi table, with optional Rekonsiliasi record creation.
 *
 * This class processes inflation data from an Excel file, validating rows and performing
 * bulk inserts or updates to the Inflasi table. It also supports a `rekonsiliasi_flag` column
 * to create Rekonsiliasi records for flagged rows, ensuring no duplicate Rekonsiliasi records
 * are created. Processing is done in chunks, stopping on the first error for data consistency.
 *
 * Key features:
 * - Validates `kd_wilayah`, `kd_komoditas`, `inflasi`, `andil`, and `rekonsiliasi_flag`.
 * - Prevents duplicate Inflasi records using `kd_komoditas-kd_wilayah` uniqueness.
 * - Prevents duplicate Rekonsiliasi records by checking existing `inflasi_id` and `bulan_tahun_id`.
 * - Uses transactions for atomicity and bulk operations for performance.
 * - Logs details and errors for debugging.
 * - Provides a summary of inserted, updated, Rekonsiliasi created, and failed rows.
 */

class DataImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    /**
     * @var array List of valid `kd_wilayah` codes from the Wilayah table.
     */
    private array $validKdWilayah;

    /**
     * @var array List of valid `kd_komoditas` codes from the Komoditas table.
     */
    private array $validKdKomoditas;

    /**
     * @var int The `bulan_tahun_id` for the import, linking to a BulanTahun record.
     */
    private int $bulanTahunId;

    /**
     * @var MessageBag Stores validation errors for each row.
     */
    private MessageBag $errors;

    /**
     * @var int Tracks the current row number in the Excel file (including header).
     */
    private int $rowNumber = 1;

    /**
     * @var string The level (`kd_level`) for the Inflasi records (e.g., regional or national).
     */
    private string $level;

    /**
     * @var Collection Existing Inflasi records for the given `bulan_tahun_id` and `kd_level`, keyed by `kd_komoditas-kd_wilayah`.
     */
    private Collection $existingInflasi;

    /**
     * @var int Count of updated Inflasi records.
     */
    private int $updatedCount = 0;

    /**
     * @var int Count of inserted Inflasi records.
     */
    private int $insertedCount = 0;

    /**
     * @var int Count of created Rekonsiliasi records.
     */
    private int $rekonsiliasiCreatedCount = 0;


    /**
     * @var int|null The row number where the first error occurred, if any.
     */
    private ?int $failedRow = null;

    /**
     * @var array Tracks `kd_komoditas-kd_wilayah` combinations in the current batch to prevent duplicates.
     */
    private array $seenKeys = [];

    /**
     * @var bool Flag to stop processing further chunks after an error or end of data.
     */
    private bool $stopProcessing = false;

    /**
     * @var int Number of rows to process per chunk for memory efficiency.
     */
    private const CHUNK_SIZE = 200;

    /**
     * @var int Count of skipped Rekonsiliasi records due to Wilayah flag rules.
     */
    private int $skippedRekonsiliasiCount = 0;

    /**
     * Constructor to initialize the import process.
     *
     * @param int $bulan The month (1-12) for the import.
     * @param int $tahun The year for the import.
     * @param string $level The level (`kd_level`) for the Inflasi records.
     */

    public function __construct(int $bulan, int $tahun, string $level)
    {
        // Load valid codes for validation
        $this->validKdWilayah = Wilayah::pluck('kd_wilayah')->toArray();
        $this->validKdKomoditas = Komoditas::pluck('kd_komoditas')->toArray();

        // Initialize error bag
        $this->errors = new MessageBag();

        // Store level
        $this->level = $level;

        // Initialize BulanTahun and load existing Inflasi records
        $this->initializeBulanTahun($bulan, $tahun);
        $this->loadExistingInflasi();
    }

    /**
     * Initializes or retrieves the BulanTahun record for the given month and year.
     *
     * @param int $bulan The month (1-12).
     * @param int $tahun The year.
     */

    private function initializeBulanTahun(int $bulan, int $tahun): void
    {
        $bulanTahun = BulanTahun::firstOrCreate(
            ['bulan' => $bulan, 'tahun' => $tahun],
            ['aktif' => 0]
        );
        $this->bulanTahunId = $bulanTahun->bulan_tahun_id;
    }

    /**
     * Loads existing Inflasi records to prevent duplicates.
     *
     * Stores records in a Collection for efficient lookup during processing.
     */
    private function loadExistingInflasi(): void
    {
        $this->existingInflasi = Inflasi::where('bulan_tahun_id', $this->bulanTahunId)
            ->where('kd_level', $this->level)
            ->select('inflasi_id', 'kd_komoditas', 'kd_wilayah')
            ->get()
            ->keyBy(fn($item) => "{$item->kd_komoditas}-{$item->kd_wilayah}");
    }

    /**
     * Processes a chunk of Excel rows.
     *
     * @param Collection $rows The rows in the current chunk.
     */
    public function collection(Collection $rows): void
    {
        // Skip processing if a previous error or stop condition was triggered
        if ($this->stopProcessing) {
            Log::info("Skipping chunk due to previous stop condition");
            return;
        }

        Log::info("Processing chunk, rows: " . count($rows) . ", starting at row {$this->rowNumber}");

        $inserts = [];
        $updates = [];

        foreach ($rows as $row) {
            $this->rowNumber++;

            // Check for empty kd_wilayah (indicates EOF)
            $kd_wilayah = trim($row['kd_wilayah'] ?? '');
            if ($kd_wilayah === '') {
                Log::info("Detected empty kd_wilayah (likely EOF) at row {$this->rowNumber}");
                $this->stopProcessing = true;
                break;
            }

            try {
                $this->validateAndPrepareRow($row, $inserts, $updates);
            } catch (Exception $e) {
                // $this->errors->add("row_{$this->rowNumber}", $e->getMessage());
                Log::error("Error at row {$this->rowNumber}: " . $e->getMessage());
                // Stop processing any more rows in this chunk
                $this->stopProcessing = true;
                break;
            }
        }

        // Process whatever rows were valid before the first error
        if (!empty($inserts) || !empty($updates)) {
            try {
                $this->processBulk($inserts, $updates);
            } catch (Exception $e) {
                Log::error("Failed to process collected rows: " . $e->getMessage());
            }
        }
    }

    /**
     * Validates a single row and prepares data for Inflasi and Rekonsiliasi.
     *
     * Checks required fields (`kd_wilayah`, `kd_komoditas`, `inflasi`), validates their values,
     * handles optional `andil`, and validates `rekonsiliasi_flag`. Prevents Inflasi duplicates
     * within the batch and prepares data for insert or update.
     *
     * @param Collection $row The Excel row data.
     * @param array &$inserts Array to store new Inflasi records.
     * @param array &$updates Array to store updates for existing Inflasi records.
     * @throws Exception If validation fails.
     */

    private function validateAndPrepareRow(Collection $row, array &$inserts, array &$updates): void
    {
        $kd_wilayah = trim($row['kd_wilayah'] ?? '');
        if ($kd_wilayah === '') {
            $this->throwError("kd_wilayah kosong");
        }
        $kd_komoditasRaw = trim($row['kd_komoditas'] ?? '');
        $nilai_inflasiRaw = trim($row['inflasi'] ?? '');
        $andilRaw = trim($row['andil'] ?? '');
        $rekonsiliasi_flag = trim($row['rekonsiliasi_flag'] ?? '0');

        // Validate kd_wilayah against Wilayah table
        if (!in_array($kd_wilayah, $this->validKdWilayah)) {
            $this->throwError("kd_wilayah '$kd_wilayah' tidak valid");
        }

        // Validate kd_komoditas
        if (is_null($kd_komoditasRaw) || $kd_komoditasRaw === '') {
            $this->throwError('kd_komoditas kosong');
        }
        // Convert to integer
        if (!is_numeric($kd_komoditasRaw)) {
            $this->throwError("kd_komoditas '$kd_komoditasRaw' harus berupa bilangan bulat");
        }
        $kd_komoditas = (int) $kd_komoditasRaw;
        // Ensure non-negative for unsignedInteger
        if ($kd_komoditas < 0) {
            $this->throwError("kd_komoditas '$kd_komoditas' tidak valid, harus non-negatif");
        }
        // Check if valid and exists in komoditas table
        if (!in_array($kd_komoditas, $this->validKdKomoditas, true)) {
            $this->throwError("kd_komoditas '$kd_komoditas' tidak valid atau tidak ditemukan");
        }

        // Validate nilai_inflasi (required, numeric)
        $nilai_inflasiClean = str_replace(',', '.', $nilai_inflasiRaw);
        if ($nilai_inflasiRaw === '' || !is_numeric($nilai_inflasiClean)) {
            $this->throwError('Nilai inflasi harus numerik');
        }
        $nilai_inflasi = round((float) $nilai_inflasiClean, 2);
        $andil = null;

        // Validate andil (optional, numeric if provided)
        if ($andilRaw !== '') {
            $andilClean = str_replace(',', '.', $andilRaw);
            if (!is_numeric($andilClean)) {
                $this->throwError('Andil harus numerik');
            }
            $andil = round((float) $andilClean, 2);
        }

        // Validate rekonsiliasi_flag (boolean, 0 or 1)
        if (!in_array($rekonsiliasi_flag, ['0', '1'])) {
            $this->throwError("rekonsiliasi_flag harus 0 atau 1");
        }

        $key = "{$kd_komoditas}-{$kd_wilayah}";
        if (isset($this->seenKeys[$key])) {
            $this->throwError("Duplikat: kombinasi kd_komoditas-kd_wilayah $key sudah ada");
        }
        $this->seenKeys[$key] = true;

        $data = [
            'bulan_tahun_id' => $this->bulanTahunId,
            'kd_level' => $this->level,
            'kd_komoditas' => $kd_komoditas,
            'kd_wilayah' => $kd_wilayah,
            'nilai_inflasi' => $nilai_inflasi,
            'andil' => $andil,
            'updated_at' => now(),
        ];

        $data['rekonsiliasi_flag'] = $rekonsiliasi_flag;

        if (isset($this->existingInflasi[$key])) {
            $updates[] = array_merge($data, ['inflasi_id' => $this->existingInflasi[$key]->inflasi_id]);
        } else {
            $inserts[] = array_merge($data, ['created_at' => now()]);
        }
    }

    /**
     * Throws a validation error and logs it.
     *
     * Adds the error to the MessageBag, sets the failed row number, and throws an exception
     * to stop processing the current row.
     *
     * @param string $message The error message.
     * @throws Exception
     */
    private function throwError(string $message): void
    {
        $this->errors->add("row_{$this->rowNumber}", $message);
        $this->failedRow = $this->rowNumber;
        throw new Exception("Kegagalan di baris {$this->rowNumber}: $message");
    }

    /**
     * Processes bulk inserts and updates for Inflasi and creates Rekonsiliasi records.
     *
     * Performs bulk inserts for new Inflasi records, updates for existing ones, and creates
     * Rekonsiliasi records for rows with `rekonsiliasi_flag = 1`, ensuring no duplicates.
     * Skips Rekonsiliasi creation if Wilayah flag = 1, or if flag = 3 and kd_level != '01'.
     * Uses a transaction to ensure atomicity and rolls back on error.
     *
     * @param array $inserts Array of new Inflasi records to insert.
     * @param array $updates Array of updates for existing Inflasi records.
     * @throws Exception If the bulk operation fails.
     */

    private function processBulk(array $inserts, array $updates): void
    {
        Log::info("Bulk processing: " . count($inserts) . " inserts, " . count($updates) . " updates");

        DB::beginTransaction();
        try {
            $rekonsiliasiData = [];
            $now = now();

            // Preload Wilayah flags to check flag = 1 (exclude) and flag = 3 (restrict for kd_level != '01')
            $wilayahFlags = Wilayah::whereIn('flag', [1, 3])
                ->pluck('flag', 'kd_wilayah')
                ->toArray();
            Log::debug("Preloaded " . count($wilayahFlags) . " Wilayah records with flag 1 or 3");

            // Handle inserts
            if (!empty($inserts)) {
                Inflasi::insert(array_map(function ($insert) {
                    // Remove rekonsiliasi_flag from Inflasi data
                    unset($insert['rekonsiliasi_flag']);
                    return $insert;
                }, $inserts));
                $this->insertedCount += count($inserts);
                // Log::debug("Inserted {$this->insertedCount} rows");

                // Collect keys for batch query to fetch new Inflasi records
                $insertKeys = array_map(function ($insert) {
                    return [
                        'kd_komoditas' => $insert['kd_komoditas'],
                        'kd_wilayah' => $insert['kd_wilayah'],
                        'rekonsiliasi_flag' => $insert['rekonsiliasi_flag'],
                    ];
                }, $inserts);

                // Fetch all new Inflasi records in bulk
                $newInflasiRecords = Inflasi::where('bulan_tahun_id', $this->bulanTahunId)
                    ->whereIn('kd_komoditas', array_column($insertKeys, 'kd_komoditas'))
                    ->whereIn('kd_wilayah', array_column($insertKeys, 'kd_wilayah'))
                    ->select('inflasi_id', 'kd_komoditas', 'kd_wilayah')
                    ->get()
                    ->keyBy(function ($item) {
                        return "{$item->kd_komoditas}-{$item->kd_wilayah}";
                    });

                // Collect Rekonsiliasi data for new records
                foreach ($insertKeys as $keyData) {
                    if ($keyData['rekonsiliasi_flag'] === '1') {
                        // Check Wilayah flag
                        $wilayahFlag = $wilayahFlags[$keyData['kd_wilayah']] ?? null;
                        if ($wilayahFlag === 1) {
                            // Log::debug("Skipping Rekonsiliasi for kd_wilayah {$keyData['kd_wilayah']} (flag = 1)");
                            $this->skippedRekonsiliasiCount++;
                            continue;
                        }
                        if ($wilayahFlag === 3 && $this->level !== '01') {
                            // Log::debug("Skipping Rekonsiliasi for kd_wilayah {$keyData['kd_wilayah']} (flag = 3, kd_level != '01')");
                            $this->skippedRekonsiliasiCount++;
                            continue;
                        }

                        $key = "{$keyData['kd_komoditas']}-{$keyData['kd_wilayah']}";
                        if (isset($newInflasiRecords[$key])) {
                            $inflasi = $newInflasiRecords[$key];
                            $rekonsiliasiData[$inflasi->inflasi_id] = [
                                'inflasi_id' => $inflasi->inflasi_id,
                                'bulan_tahun_id' => $this->bulanTahunId,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                }
            }

            // Handle updates
            if (!empty($updates)) {
                $updatedRows = 0;
                foreach ($updates as $update) {
                    $affected = Inflasi::where('inflasi_id', $update['inflasi_id'])
                        ->update([
                            'nilai_inflasi' => $update['nilai_inflasi'],
                            'andil' => $update['andil'],
                            'updated_at' => $update['updated_at'],
                        ]);
                    $updatedRows += $affected;

                    // Collect Rekonsiliasi data for updated rows
                    if ($update['rekonsiliasi_flag'] === '1') {

                        // Check Wilayah flag
                        $wilayahFlag = $wilayahFlags[$update['kd_wilayah']] ?? null;
                        if ($wilayahFlag === 1) {
                            // Log::debug("Skipping Rekonsiliasi for kd_wilayah {$update['kd_wilayah']} (flag = 1)");
                            $this->skippedRekonsiliasiCount++;
                            continue;
                        }
                        if ($wilayahFlag === 3 && $this->level !== '01') {
                            // Log::debug("Skipping Rekonsiliasi for kd_wilayah {$update['kd_wilayah']} (flag = 3, kd_level != '01')");
                            $this->skippedRekonsiliasiCount++;
                            continue;
                        }

                        $rekonsiliasiData[$update['inflasi_id']] = [
                            'inflasi_id' => $update['inflasi_id'],
                            'bulan_tahun_id' => $this->bulanTahunId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
                $this->updatedCount += $updatedRows;
                Log::debug("Updated {$updatedRows} rows, total updated: {$this->updatedCount}");
            }

            // Process Rekonsiliasi records, preventing duplicates
            if (!empty($rekonsiliasiData)) {
                $inflasiIds = array_keys($rekonsiliasiData);

                // Check for existing Rekonsiliasi records in bulk
                $existingRekonsiliasiIds = Rekonsiliasi::whereIn('inflasi_id', $inflasiIds)
                    ->where('bulan_tahun_id', $this->bulanTahunId)
                    ->pluck('inflasi_id')
                    ->toArray();

                // Filter out existing Rekonsiliasi records
                $rekonsiliasiData = array_filter(
                    $rekonsiliasiData,
                    fn($data) => !in_array($data['inflasi_id'], $existingRekonsiliasiIds)
                );

                // Insert new Rekonsiliasi records
                if (!empty($rekonsiliasiData)) {
                    Rekonsiliasi::insert(array_values($rekonsiliasiData));
                    $this->rekonsiliasiCreatedCount += count($rekonsiliasiData);
                    Log::debug("Inserted " . count($rekonsiliasiData) . " Rekonsiliasi records");
                }
            }

            // Log warning for skipped Rekonsiliasi records
            // if ($this->skippedRekonsiliasiCount > 0) {
            //     Log::warning("Total rekonsiliasi skipped: {$this->skippedRekonsiliasiCount}");
            // }

            DB::commit();
            Log::debug("Transaction committed");

            // Stop processing if the chunk is smaller than CHUNK_SIZE (end of data)
            if (count($inserts) + count($updates) < self::CHUNK_SIZE) {
                $this->stopProcessing = true;
                Log::info("Stopping processing: chunk size " . (count($inserts) + count($updates)) . " < " . self::CHUNK_SIZE);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Bulk processing failed: " . $e->getMessage());
            $this->throwError("Bulk error: " . $e->getMessage());
        }
    }

    public function chunkSize(): int
    {
        return self::CHUNK_SIZE;
    }

    public function getErrors(): MessageBag
    {
        return $this->errors;
    }

    public function getSummary(): array
    {
        return [
            'updated' => $this->updatedCount,
            'inserted' => $this->insertedCount,
            'rekonsiliasi_created' => $this->rekonsiliasiCreatedCount,
            'skipped_rekonsiliasi' => $this->skippedRekonsiliasiCount, // Add skipped count
            'failed_row' => $this->failedRow,
        ];
    }
}
