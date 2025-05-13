<?php

namespace App\Imports;

use App\Models\BulanTahun;
use App\Models\Inflasi;
use App\Models\Komoditas;
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
 * Import Excel data into the Inflasi table, stopping immediately on error.
 */
class DataImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private array $validKdWilayah;
    private array $validKdKomoditas;
    private int $bulanTahunId;
    private MessageBag $errors;
    private int $rowNumber = 1;
    private string $level;
    private Collection $existingInflasi;
    private int $updatedCount = 0;
    private int $insertedCount = 0;
    private ?int $failedRow = null;
    private array $seenKeys = [];
    private bool $stopProcessing = false;
    private const CHUNK_SIZE = 200; // Increased chunk size for fewer transactions

    public function __construct(int $bulan, int $tahun, string $level)
    {
        $this->validKdWilayah = Wilayah::pluck('kd_wilayah')->toArray();
        $this->validKdKomoditas = Komoditas::pluck('kd_komoditas')->toArray();
        $this->errors = new MessageBag();
        $this->level = $level;
        $this->initializeBulanTahun($bulan, $tahun);
        $this->loadExistingInflasi();
    }

    private function initializeBulanTahun(int $bulan, int $tahun): void
    {
        $bulanTahun = BulanTahun::firstOrCreate(
            ['bulan' => $bulan, 'tahun' => $tahun],
            ['aktif' => 0]
        );
        $this->bulanTahunId = $bulanTahun->bulan_tahun_id;
    }

    private function loadExistingInflasi(): void
    {
        $this->existingInflasi = Inflasi::where('bulan_tahun_id', $this->bulanTahunId)
            ->where('kd_level', $this->level)
            ->select('inflasi_id', 'kd_komoditas', 'kd_wilayah')
            ->get()
            ->keyBy(fn($item) => "{$item->kd_komoditas}-{$item->kd_wilayah}");
    }

    public function collection(Collection $rows): void
    {
        if ($this->stopProcessing) {
            Log::info("Skipping chunk due to previous stop condition");
            return;
        }

        Log::info("Processing chunk, rows: " . count($rows) . ", starting at row {$this->rowNumber}");

        $inserts = [];
        $updates = [];

        foreach ($rows as $row) {
            $this->rowNumber++;

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


    private function validateAndPrepareRow(Collection $row, array &$inserts, array &$updates): void
    {
        $kd_wilayah = trim($row['kd_wilayah'] ?? '');
        if ($kd_wilayah === '') {
            $this->throwError("kd_wilayah kosong");
        }
        $kd_komoditasRaw = trim($row['kd_komoditas'] ?? '');
        $nilai_inflasiRaw = trim($row['inflasi'] ?? '');
        $andilRaw = trim($row['andil'] ?? '');

        if (!in_array($kd_wilayah, $this->validKdWilayah)) {
            $this->throwError("kd_wilayah '$kd_wilayah' tidak valid");
        }

        if ($kd_komoditasRaw === '') {
            $this->throwError('kd_komoditas kosong');
        }
        $kd_komoditas = str_pad($kd_komoditasRaw, 3, '0', STR_PAD_LEFT);
        if (!in_array($kd_komoditas, $this->validKdKomoditas)) {
            $this->throwError("kd_komoditas '$kd_komoditas' tidak valid");
        }

        $nilai_inflasiClean = str_replace(',', '.', $nilai_inflasiRaw);
        if ($nilai_inflasiRaw === '' || !is_numeric($nilai_inflasiClean)) {
            $this->throwError('Nilai inflasi harus numerik');
        }
        $nilai_inflasi = round((float) $nilai_inflasiClean, 2);
        $andil = null;

        if ($andilRaw !== '') {
            $andilClean = str_replace(',', '.', $andilRaw);
            if (!is_numeric($andilClean)) {
                $this->throwError('Andil harus numerik');
            }
            $andil = round((float) $andilClean, 4);
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

        if (isset($this->existingInflasi[$key])) {
            $updates[] = array_merge($data, ['inflasi_id' => $this->existingInflasi[$key]->inflasi_id]);
        } else {
            $inserts[] = array_merge($data, ['created_at' => now()]);
        }
    }

    private function throwError(string $message): void
    {
        $this->errors->add("row_{$this->rowNumber}", $message);
        $this->failedRow = $this->rowNumber;
        throw new Exception("Kegagalan di baris {$this->rowNumber}: $message");
    }

    private function processBulk(array $inserts, array $updates): void
    {
        Log::info("Bulk processing: " . count($inserts) . " inserts, " . count($updates) . " updates");

        DB::beginTransaction();
        try {
            // Handle inserts
            if (!empty($inserts)) {
                Inflasi::insert($inserts);
                $this->insertedCount += count($inserts);
                Log::debug("Inserted {$this->insertedCount} rows");
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
                }
                $this->updatedCount += $updatedRows;
                Log::debug("Updated {$updatedRows} rows, total updated: {$this->updatedCount}");
            }

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
            'failed_row' => $this->failedRow,
        ];
    }
}
