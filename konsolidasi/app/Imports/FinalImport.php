<?php

namespace App\Imports;

use App\Models\BulanTahun;
use App\Models\Inflasi;
use App\Models\Komoditas;
use App\Models\Wilayah;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinalImport implements ToCollection, WithHeadingRow, WithChunkReading
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
     * @var bool Enables debug mode for single-row processing and detailed logging.
     */
    private bool $debugMode;

    /**
     * @var int Number of rows to process per chunk for memory efficiency.
     */
    private const CHUNK_SIZE = 200;

    /**
     * Constructor to initialize the import process.
     *
     * @param int $bulan The month (1-12) for the import.
     * @param int $tahun The year for the import.
     * @param string $level The level (`kd_level`) for the Inflasi records.
     * @param bool $debugMode Enables debug mode for single-row processing.
     */
    public function __construct(int $bulan, int $tahun, string $level, bool $debugMode = false)
    {
        // Load valid codes for validation
        $this->validKdWilayah = Wilayah::pluck('kd_wilayah')->toArray();
        $this->validKdKomoditas = Komoditas::pluck('kd_komoditas')->toArray();

        // Initialize error bag
        $this->errors = new MessageBag();

        // Store level and debug mode
        $this->level = $level;
        $this->debugMode = $debugMode;

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
        $bulanTahun = BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->first();
        if (!$bulanTahun) {
            throw new \Exception("No BulanTahun record found for bulan {$bulan} and tahun {$tahun}");
        }
        $this->bulanTahunId = $bulanTahun->bulan_tahun_id;
    }

    /**
     * Loads existing Inflasi records to check for updates or inserts.
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
            Log::info("Skipping chunk due to previous stop condition at row {$this->rowNumber}");
            return;
        }

        Log::info("Processing chunk, rows: " . count($rows) . ", starting at row {$this->rowNumber}");

        $updates = [];

        foreach ($rows as $row) {
            $this->rowNumber++;

            // Check for empty kd wilayah (indicates EOF)
            $kd_wilayah = trim($row['kd_wilayah'] ?? '');
            if ($kd_wilayah === '') {
                Log::info("Detected empty kd_wilayah (likely EOF) at row {$this->rowNumber}");
                $this->stopProcessing = true;
                break;
            }

            try {
                $this->validateAndPrepareRow($row, $updates);
            } catch (\Exception $e) {
                Log::error("Error at row {$this->rowNumber}: " . $e->getMessage());
                $this->stopProcessing = true;
                break;
            }
        }

        // Process valid rows before any error
        if (!empty($updates)) {
            if ($this->debugMode) {
                foreach ($updates as $update) {
                    $this->processSingleUpdate($update);
                }
            } else {
                try {
                    $this->processBulk($updates);
                } catch (\Exception $e) {
                    Log::error("Failed to process collected rows: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Validates a single row and prepares data for Inflasi.
     *
     * Checks required fields (`kd_wilayah`, `kd_komoditas`, `inflasi`), validates their values,
     * and handles optional `andil`. Prevents duplicates within the batch and prepares data
     * for insert or update.
     *
     * @param Collection $row The Excel row data.
     * @param array &$inserts Array to store new Inflasi records.
     * @param array &$updates Array to store updates for existing Inflasi records.
     * @throws \Exception If validation fails.
     */
    private function validateAndPrepareRow(Collection $row, array &$updates): void
    {
        $kd_wilayah = trim($row['kd_wilayah'] ?? '');
        if ($kd_wilayah === '') {
            $this->throwError("kd_wilayah kosong");
        }
        $kd_komoditasRaw = trim($row['kd_komoditas'] ?? '');
        $final_inflasiRaw = trim($row['final_inflasi'] ?? '');
        $final_andilRaw = trim($row['final_andil'] ?? '');

        // Validate kd_wilayah
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

        // Check if valid and exists in komoditas table
        if (!in_array($kd_komoditas, $this->validKdKomoditas, true)) {
            $this->throwError("kd_komoditas '$kd_komoditas' tidak valid atau tidak ditemukan");
        }

        // Validate final_inflasi (required, numeric)
        $final_inflasiClean = str_replace(',', '.', $final_inflasiRaw);
        if ($final_inflasiRaw === '' || !is_numeric($final_inflasiClean)) {
            $this->throwError('final_inflasi harus numerik');
        }
        $final_inflasi = round((float) $final_inflasiClean, 2);

        // Validate final_andil (optional, numeric if provided)
        $final_andil = null;
        if ($final_andilRaw !== '') {
            $final_andilClean = str_replace(',', '.', $final_andilRaw);
            if (!is_numeric($final_andilClean)) {
                $this->throwError('final_andil harus numerik');
            }
            $final_andil = round((float) $final_andilClean, 2);
        }

        // Check for duplicates in the current batch
        $key = "{$kd_komoditas}-{$kd_wilayah}";
        if (isset($this->seenKeys[$key])) {
            $this->throwError("Duplikat: kombinasi kd_komoditas-kd_wilayah $key sudah ada");
        }
        $this->seenKeys[$key] = true;

        if (!isset($this->existingInflasi[$key])) {
            $this->throwError("No existing Inflasi record for kd_komoditas-kd_wilayah {$key} at row {$this->rowNumber}"); //edit
        }

        $updates[] = [
            'inflasi_id' => $this->existingInflasi[$key]->inflasi_id,
            'final_inflasi' => $final_inflasi,
            'final_andil' => $final_andil,
            'updated_at' => now(),
        ];
    }

    /**
     * Throws a validation error and logs it.
     *
     * Adds the error to the MessageBag, sets the failed row number, and throws an exception
     * to stop processing the current row.
     *
     * @param string $message The error message.
     * @throws \Exception
     */
    private function throwError(string $message): void
    {
        $this->errors->add("row_{$this->rowNumber}", $message);
        $this->failedRow = $this->rowNumber;
        throw new \Exception("Kegagalan di baris {$this->rowNumber}: $message");
    }

    /**
     * Processes bulk inserts and updates for Inflasi records.
     *
     * Performs bulk inserts for new Inflasi records and updates for existing ones.
     * Uses a transaction to ensure atomicity and rolls back on error.
     *
     * @param array $updates Array of updates for existing Inflasi records.
     * @throws \Exception If the bulk operation fails.
     */
    private function processBulk(array $updates): void
    {
        Log::info("Bulk processing: "  . count($updates) . " updates");

        DB::beginTransaction();
        try {
            if (!empty($updates)) {
                $updatedRows = 0;
                foreach ($updates as $update) {
                    $affected = DB::table('inflasi')
                        ->where('inflasi_id', $update['inflasi_id'])
                        ->update([
                            'final_inflasi' => $update['final_inflasi'],
                            'final_andil' => $update['final_andil'],
                            'updated_at' => $update['updated_at'],
                        ]);
                    $updatedRows += $affected;
                }
                $this->updatedCount += $updatedRows;
                Log::debug("Updated {$updatedRows} rows, total updated: {$this->updatedCount}");
            }

            DB::commit();
            Log::debug("Transaction committed");

            // Stop processing if the chunk is smaller than CHUNK_SIZE (end of data)
            if (count($updates) < self::CHUNK_SIZE) {
                $this->stopProcessing = true;
                Log::info("Stopping processing: chunk size " . count($updates) . " < " . self::CHUNK_SIZE);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Bulk processing failed: " . $e->getMessage());
            $this->throwError("Bulk error: " . $e->getMessage());
        }
    }

    /**
     * Processes a single update in debug mode.
     *
     * Updates an existing Inflasi record and logs the result.
     *
     * @param array $data The data to update.
     */
    private function processSingleUpdate(array $data): void
    {
        Log::info("Debug: Attempting to update row {$this->rowNumber}: " . json_encode($data));
        try {
            $affected = DB::table('inflasi')
                ->where('inflasi_id', $data['inflasi_id'])
                ->update([
                    'final_inflasi' => $data['final_inflasi'],
                    'final_andil' => $data['final_andil'],
                    'updated_at' => $data['updated_at'],
                ]);
            if ($affected > 0) {
                $this->updatedCount++;
                Log::info("Debug: Successfully updated row {$this->rowNumber}");
            }
        } catch (\Exception $e) {
            $this->errors->add("row_{$this->rowNumber}", "Update failed: " . $e->getMessage());
            $this->failedRow = $this->rowNumber;
            Log::error("Debug: Update failed at row {$this->rowNumber}: " . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->stopProcessing = true;
        }
    }

    /**
     * Returns the chunk size for processing.
     *
     * @return int The number of rows per chunk.
     */
    public function chunkSize(): int
    {
        return self::CHUNK_SIZE;
    }

    /**
     * Returns the errors encountered during import.
     *
     * @return MessageBag The error messages.
     */
    public function getErrors(): MessageBag
    {
        return $this->errors;
    }

    /**
     * Returns a summary of the import process.
     *
     * @return array Summary of updated and inserted records, and the failed row (if any).
     */
    public function getSummary(): array
    {
        return [
            'updated' => $this->updatedCount,
            'failed_row' => $this->failedRow,
        ];
    }
}
