<?php

namespace App\Imports;

use App\Models\Inflasi;
use App\Models\Wilayah;
use App\Models\Komoditas;
use App\Models\BulanTahun;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private $validKdWilayah;
    private $validKdKomoditas;
    private $bulanTahunId;
    private $errors;
    private $rowNumber;

    private $level;
    private $existingInflasi;
    private $updatedCount = 0;
    private $insertedCount = 0;
    private $failedRow = null;
    private $seenKeys = []; // Track keys to detect in-file duplicates

    // for phantom rows or messy formatting.
    private $stopAfterSmallChunk = false;
    public function __construct($bulan, $tahun, $level)
    {

        $this->validKdWilayah = Wilayah::pluck('kd_wilayah')->toArray();
        $this->validKdKomoditas = Komoditas::pluck('kd_komoditas')->toArray();
        $this->errors = new MessageBag();
        $this->rowNumber = 1;
        $this->level = $level;

        $bulanTahun = BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->first();
        if ($bulanTahun) {
            $this->bulanTahunId = $bulanTahun->bulan_tahun_id;
        } else {
            $bulanTahun = BulanTahun::create(['bulan' => $bulan, 'tahun' => $tahun, 'aktif' => 0]);
            $this->bulanTahunId = $bulanTahun->bulan_tahun_id;
        }

        // Load existing data for the month
        $this->existingInflasi = Inflasi::where('bulan_tahun_id', $this->bulanTahunId)
            ->where('kd_level', $this->level)
            ->get()
            ->keyBy(fn($item) => "{$item->kd_komoditas}-{$item->kd_wilayah}");
    }

    /**
     * Process a chunk of Excel rows
     * Validates data, queues updates/inserts, stops at first failure or duplicate.
     */
    public function collection(Collection $rows)
    {
        Log::info("Processing chunk, rows: " . count($rows) . ", starting at row {$this->rowNumber}");

        if ($this->stopAfterSmallChunk) {
            Log::info("Skipping chunk due to previous small chunk stop");
            return;
        }

        if ($this->failedRow !== null) {
            Log::warning("Skipping chunk due to previous failure at row {$this->failedRow}");
            return;
        }

        $updates = [];
        $inserts = [];

        foreach ($rows as $row) {
            $this->rowNumber++;
            Log::info("Processing row {$this->rowNumber}: " . json_encode($row));

            if ($this->failedRow !== null) {
                Log::warning("Stopping import due to previous failure at row {$this->failedRow}");
                break;
            }

            // Validation: kd_wilayah
            $kd_wilayah = $row['kd_wilayah'] ?? '0';
            if (is_null($kd_wilayah) || $kd_wilayah === '') {
                $kd_wilayah = '0';
            }
            Log::info("kd_wilayah after processing: {$kd_wilayah}");
            if (!in_array($kd_wilayah, $this->validKdWilayah)) {
                $this->errors->add("row_{$this->rowNumber}", "kd_wilayah '$kd_wilayah' tidak valid. Cek master wilayah");
                $this->failedRow = $this->rowNumber;
                Log::error("Invalid kd_wilayah: {$kd_wilayah} at row {$this->rowNumber}");
                break;
            }

            // Validation: kd_komoditas
            if (!isset($row['kd_komoditas']) || $row['kd_komoditas'] === '' || $row['kd_komoditas'] === null) {
                $this->errors->add("row_{$this->rowNumber}", "kd_komoditas kosong");
                $this->failedRow = $this->rowNumber;
                Log::error("kd_komoditas is required at row {$this->rowNumber}");
                break;
            }
            $kd_komoditas = str_pad((string) $row['kd_komoditas'], 3, '0', STR_PAD_LEFT);
            Log::info("kd_komoditas after normalization: {$kd_komoditas}");
            if (!in_array($kd_komoditas, $this->validKdKomoditas)) {
                $this->errors->add("row_{$this->rowNumber}", "kd_komoditas '{$row['kd_komoditas']}' tidak valid (telah diperbaiki menjadi '$kd_komoditas'). Cek master komoditas");
                $this->failedRow = $this->rowNumber;
                Log::error("Invalid kd_komoditas: {$kd_komoditas} at row {$this->rowNumber}");
                break;
            }

            // Validation: inflasi
            $inflasiRaw = $row['inflasi'] ?? null;
            if (!isset($inflasiRaw) || !is_numeric($inflasiRaw)) {
                $this->errors->add("row_{$this->rowNumber}", "Inflasi harus numerik");
                $this->failedRow = $this->rowNumber;
                Log::error("Invalid inflasi at row {$this->rowNumber}: " . json_encode($inflasiRaw ?? 'null'));
                break;
            }
            // Round to 2 decimal places to match database precision
            $inflasi = round((float) $inflasiRaw, 2);
            Log::info("Inflasi normalized to 2 decimal places: $inflasi");

            // Validation: andil
            // Validation: andil
            $andilRaw = $row['andil'] ?? null;
            $andil = null;
            if (isset($andilRaw) && $andilRaw !== '') {
                if (!is_numeric($andilRaw)) {
                    $this->errors->add("row_{$this->rowNumber}", "Andil harus numerik");
                    $this->failedRow = $this->rowNumber;
                    Log::error("Invalid andil at row {$this->rowNumber}: " . json_encode($andilRaw));
                    break;
                }
                // Round to 2 decimal places
                $andil = round((float) $andilRaw, 2);
                Log::info("Andil normalized to 2 decimal places: $andil");
            }

            // Check for duplicate key in the file
            $key = "{$kd_komoditas}-{$kd_wilayah}";
            if (isset($this->seenKeys[$key])) {
                $this->errors->add("row_{$this->rowNumber}", "Duplikat ditemukan: kombinasi kd_komoditas '$kd_komoditas' dan kd_wilayah '$kd_wilayah' sudah ada.");
                $this->failedRow = $this->rowNumber;
                Log::error("Duplicate key: {$key} at row {$this->rowNumber}");
                break;
            }
            $this->seenKeys[$key] = true;

            // Prepare data for DB
            $data = [
                'bulan_tahun_id' => $this->bulanTahunId,
                'kd_level' => $this->level,
                'kd_komoditas' => $kd_komoditas,
                'kd_wilayah' => $kd_wilayah,
                'inflasi' => (float) $row['inflasi'],
                'andil' => $andil,
                'updated_at' => now(),
            ];

            if (isset($this->existingInflasi[$key])) {
                $updates[] = array_merge($data, ['inflasi_id' => $this->existingInflasi[$key]->inflasi_id]);
            } else {
                $inserts[] = array_merge($data, ['created_at' => now()]);
            }

            Log::error("Data preparation ok");
        }

        // if ($this->failedRow === null && (!empty($updates) || !empty($inserts))) {
        //     Log::error("Beginning transaction");
        //     DB::beginTransaction();
        //     try {
        //         if (!empty($inserts)) {
        //             foreach (array_chunk($inserts, 100) as $chunk) {
        //                 Inflasi::insert($chunk);
        //                 $this->insertedCount += count($chunk);
        //             }
        //             Log::error("Insert done");
        //         }
        //         if (!empty($updates)) {
        //             Log::error("Update not empty");
        //             foreach (array_chunk($updates, 100) as $chunk) {
        //                 foreach ($chunk as $update) {
        //                     DB::table('inflasi')
        //                         ->where('inflasi_id', $update['inflasi_id'])
        //                         ->update([
        //                             'inflasi' => $update['inflasi'],
        //                             'andil' => $update['andil'],
        //                             'updated_at' => $update['updated_at']
        //                         ]);
        //                 }
        //                 $this->updatedCount += count($chunk);
        //                 Log::info("Updated {$this->updatedCount} rows");
        //             }
        //         }
        //         DB::commit();

        //         // if (count($rows) < $this->chunkSize()) {
        //         //     Log::info("Partial chunk of " . count($rows) . " rows processed, stopping further chunks");
        //         //     $this->stopAfterSmallChunk = true;
        //         // }
        //     } catch (\Exception $e) {
        //         DB::rollBack();
        //         $this->errors->add("row_{$this->rowNumber}", "Database error: " . $e->getMessage());
        //         $this->failedRow = $this->rowNumber;
        //         Log::error("Database error at row {$this->rowNumber}: " . $e->getMessage());
        //     }

        //     Log::info("Chunk completed, inserted: {$this->insertedCount}, updated: {$this->updatedCount}, errors: " . $this->errors->count());
        // }

        if ($this->failedRow === null && (!empty($updates) || !empty($inserts))) {
            Log::info("Preparing to process: " . count($inserts) . " inserts, " . count($updates) . " updates");
            Log::info("Beginning transaction");
            DB::beginTransaction();
            try {
                if (!empty($inserts)) {
                    Log::info("Inserting " . count($inserts) . " rows");
                    // Enable query logging
                    DB::enableQueryLog();
                    Log::info("DB query log enabled");
                    Inflasi::insert($inserts);
                    $queries = DB::getQueryLog();
                    Log::info("Executed insert query: " . json_encode($queries));
                    $this->insertedCount += count($inserts);
                    Log::info("Inserted {$this->insertedCount} rows");
                } else {
                    Log::info("No inserts to process");
                }

                if (!empty($updates)) {
                    Log::info("Updating " . count($updates) . " rows");
                    foreach ($updates as $update) {
                        $affected = DB::table('inflasi')
                            ->where('inflasi_id', $update['inflasi_id'])
                            ->update([
                                'inflasi' => $update['inflasi'],
                                'andil' => $update['andil'],
                                'updated_at' => $update['updated_at']
                            ]);
                        $this->updatedCount += $affected;
                        Log::info("Update for inflasi_id {$update['inflasi_id']} affected $affected rows");
                    }
                    Log::info("Updated total {$this->updatedCount} rows");
                } else {
                    Log::info("No updates to process");
                }

                DB::commit();
                Log::info("Transaction committed");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->errors->add("row_{$this->rowNumber}", "Database error: " . $e->getMessage());
                $this->failedRow = $this->rowNumber;
                Log::error("Database error at row {$this->rowNumber}: " . $e->getMessage(), [
                    'exception' => $e->getTraceAsString()
                ]);
            }
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getSummary()
    {
        return [
            'updated' => $this->updatedCount,
            'inserted' => $this->insertedCount,
            'failed_row' => $this->failedRow,
        ];
    }
}
