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
            Log::info("Skipping chunk due to previous error or stop condition");
            return;
        }

        Log::info("Processing chunk, rows: " . count($rows) . ", starting at row {$this->rowNumber}");

        $inserts = [];
        $updates = [];

        try {
            foreach ($rows as $row) {
                $this->rowNumber++;

                if ($this->isEmptyRow($row)) {
                    Log::info("Detected end of file at row {$this->rowNumber}");
                    $this->stopProcessing = true;
                    break;
                }

                $this->validateAndPrepareRow($row, $inserts, $updates);
            }

            if (!empty($inserts) || !empty($updates)) {
                $this->processBulk($inserts, $updates);
            }
        } catch (Exception $e) {
            $this->stopProcessing = true;
            Log::error("Import stopped due to error at row {$this->rowNumber}: " . $e->getMessage());
        }
    }

    private function isEmptyRow(Collection $row): bool
    {
        return empty(array_filter($row->toArray(), fn($value) => $value !== null && $value !== ''));
    }

    private function validateAndPrepareRow(Collection $row, array &$inserts, array &$updates): void
    {
        $kd_wilayah = trim($row['kd_wilayah'] ?? '0') ?: '0';
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
        if ($kd_wilayah === '0') {
            if ($andilRaw === '') {
                Log::warning("Missing andil for kd_wilayah '0' at row {$this->rowNumber}");
                $this->errors->add("row_{$this->rowNumber}_warning", "Andil diperlukan untuk kd_wilayah nasional");
            } else {
                $andilClean = str_replace(',', '.', $andilRaw);
                if (!is_numeric($andilClean)) {
                    $this->throwError('Andil harus numerik');
                }
                $andil = round((float) $andilClean, 4);
            }
        } elseif ($andilRaw !== '') {
            $andilClean = str_replace(',', '.', $andilRaw);
            if (!is_numeric($andilClean)) {
                $this->throwError('Andil harus numerik');
            }
            Log::warning("Ignoring andil for kd_wilayah '$kd_wilayah' at row {$this->rowNumber}");
            $this->errors->add("row_{$this->rowNumber}_warning", "Andil diabaikan untuk kd_wilayah '$kd_wilayah'");
        }

        $key = "{$kd_komoditas}-{$kd_wilayah}";
        if (isset($this->seenKeys[$key])) {
            $this->throwError("Duplikat: $key sudah ada");
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
        throw new Exception("Validation failed at row {$this->rowNumber}: $message");
    }

    private function processBulk(array $inserts, array $updates): void
    {
        Log::info("Bulk processing: " . count($inserts) . " inserts, " . count($updates) . " updates");

        DB::beginTransaction();
        try {
            if (!empty($inserts)) {
                Inflasi::insert($inserts);
                $this->insertedCount += count($inserts);
            }

            if (!empty($updates)) {
                // Batch update using a single query
                $updateValues = [];
                foreach ($updates as $update) {
                    $updateValues[] = [
                        'inflasi_id' => $update['inflasi_id'],
                        'nilai_inflasi' => $update['nilai_inflasi'],
                        'and CACILITATIONil' => $update['andil'],
                        'updated_at' => $update['updated_at'],
                    ];
                }

                // Use a CASE statement for bulk update
                if (!empty($updateValues)) {
                    $caseStatements = [];
                    $ids = array_column($updateValues, 'inflasi_id');

                    foreach ($updateValues as $update) {
                        $caseStatements[] = "WHEN {$update['inflasi_id']} THEN ?";
                        $bindings[] = $update['nilai_inflasi'];
                        $bindings[] = $update['andil'];
                        $bindings[] = $update['updated_at'];
                    }

                    $caseClause = implode(' ', $caseStatements);
                    $bindings = array_merge($bindings ?? [], $ids);

                    $affected = DB::update("
                        UPDATE inflasi
                        SET
                            nilai_inflasi = CASE inflasi_id $caseClause END,
                            andil = CASE inflasi_id $caseClause END,
                            updated_at = CASE inflasi_id $caseClause END
                        WHERE inflasi_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")
                    ", $bindings);

                    $this->updatedCount += $affected;
                }
            }

            DB::commit();
            if (count($inserts) + count($updates) < self::CHUNK_SIZE) {
                $this->stopProcessing = true;
            }
        } catch (Exception $e) {
            DB::rollBack();
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
