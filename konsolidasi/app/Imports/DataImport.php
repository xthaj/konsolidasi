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

    private $debugMode = false; // Toggle this to true for row-by-row debugging

    public function __construct($bulan, $tahun, $level, $debugMode = false)
    {
        $this->validKdWilayah = Wilayah::pluck('kd_wilayah')->toArray();
        $this->validKdKomoditas = Komoditas::pluck('kd_komoditas')->toArray();
        $this->errors = new MessageBag();
        $this->rowNumber = 1;
        $this->level = $level;
        $this->debugMode = $debugMode;

        $bulanTahun = BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->first();
        if ($bulanTahun) {
            $this->bulanTahunId = $bulanTahun->bulan_tahun_id;
        } else {
            $bulanTahun = BulanTahun::create(['bulan' => $bulan, 'tahun' => $tahun, 'aktif' => 0]);
            $this->bulanTahunId = $bulanTahun->bulan_tahun_id;
        }

        $this->existingInflasi = Inflasi::where('bulan_tahun_id', $this->bulanTahunId)
            ->where('kd_level', $this->level)
            ->get()
            ->keyBy(fn($item) => "{$item->kd_komoditas}-{$item->kd_wilayah}");
    }

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
            $rowData = $row->toArray();
            Log::info("Processing row {$this->rowNumber}: " . json_encode($row->toArray()));

            if (empty(array_filter($rowData, fn($value) => $value !== null && $value !== ''))) {
                Log::info("Detected end of file at row {$this->rowNumber}");
                $this->stopAfterSmallChunk = true;
                break;
            }

            // Validation: kd_wilayah
            $kd_wilayah = trim($row['kd_wilayah'] ?? '0');
            if ($kd_wilayah === '') {
                $kd_wilayah = '0';
            }
            if (!in_array($kd_wilayah, $this->validKdWilayah)) {
                $this->errors->add("row_{$this->rowNumber}", "kd_wilayah '$kd_wilayah' tidak valid");
                $this->failedRow = $this->rowNumber;
                break;
            }

            // Validation: kd_komoditas
            $kd_komoditasRaw = trim($row['kd_komoditas'] ?? '');
            if ($kd_komoditasRaw === '') {
                $this->errors->add("row_{$this->rowNumber}", "kd_komoditas kosong");
                $this->failedRow = $this->rowNumber;
                break;
            }
            $kd_komoditas = str_pad($kd_komoditasRaw, 3, '0', STR_PAD_LEFT);
            if (!in_array($kd_komoditas, $this->validKdKomoditas)) {
                $this->errors->add("row_{$this->rowNumber}", "kd_komoditas '$kd_komoditas' tidak valid");
                $this->failedRow = $this->rowNumber;
                break;
            }

            // Validation: inflasi
            $inflasiRaw = trim($row['inflasi'] ?? '');
            if ($inflasiRaw === '' || !is_numeric($inflasiRaw)) {
                $this->errors->add("row_{$this->rowNumber}", "Inflasi harus numerik");
                $this->failedRow = $this->rowNumber;
                Log::error("Invalid inflasi at row {$this->rowNumber}: " . json_encode($inflasiRaw));
                break;
            }
            $inflasiClean = str_replace(',', '.', $inflasiRaw);
            if (!is_numeric($inflasiClean)) {
                $this->errors->add("row_{$this->rowNumber}", "Inflasi tidak valid setelah normalisasi");
                $this->failedRow = $this->rowNumber;
                break;
            }
            $inflasi = round((float) $inflasiClean, 2);

            // Validation: andil
            $andilRaw = trim($row['andil'] ?? '');
            $andil = null;
            if ($andilRaw !== '') {
                if (!is_numeric($andilRaw)) {
                    $this->errors->add("row_{$this->rowNumber}", "Andil harus numerik");
                    $this->failedRow = $this->rowNumber;
                    Log::error("Invalid andil at row {$this->rowNumber}: " . json_encode($andilRaw));
                    break;
                }
                $andilClean = str_replace(',', '.', $andilRaw);
                if (!is_numeric($andilClean)) {
                    $this->errors->add("row_{$this->rowNumber}", "Andil tidak valid setelah normalisasi");
                    $this->failedRow = $this->rowNumber;
                    break;
                }
                $andil = round((float) $andilClean, 2);
            }

            // Duplicate check
            $key = "{$kd_komoditas}-{$kd_wilayah}";
            if (isset($this->seenKeys[$key])) {
                $this->errors->add("row_{$this->rowNumber}", "Duplikat: $key sudah ada");
                $this->failedRow = $this->rowNumber;
                break;
            }
            $this->seenKeys[$key] = true;

            // Prepare data
            $data = [
                'bulan_tahun_id' => $this->bulanTahunId,
                'kd_level' => $this->level,
                'kd_komoditas' => $kd_komoditas,
                'kd_wilayah' => $kd_wilayah,
                'inflasi' => $inflasi,
                'andil' => $andil,
                'updated_at' => now(),
            ];

            if (isset($this->existingInflasi[$key])) {
                $updates[] = array_merge($data, ['inflasi_id' => $this->existingInflasi[$key]->inflasi_id]);
            } else {
                $inserts[] = array_merge($data, ['created_at' => now()]);
            }

            // Debug mode: Insert row-by-row
            if ($this->debugMode && $this->failedRow === null) {
                if (!empty($inserts)) {
                    $this->processSingleInsert(end($inserts));
                    $inserts = []; // Clear after processing
                }
                if (!empty($updates)) {
                    $this->processSingleUpdate(end($updates));
                    $updates = []; // Clear after processing
                }
            }
        }

        // Normal mode: Process in bulk
        if (!$this->debugMode && $this->failedRow === null && (!empty($updates) || !empty($inserts))) {
            $this->processBulk($inserts, $updates);
        }
    }

    private function processSingleInsert(array $data)
    {
        Log::info("Debug: Attempting to insert row {$this->rowNumber}: " . json_encode($data));
        try {
            DB::table('inflasi')->insert($data);
            $this->insertedCount++;
            Log::info("Debug: Successfully inserted row {$this->rowNumber}");
        } catch (\Exception $e) {
            $this->errors->add("row_{$this->rowNumber}", "Insert failed: " . $e->getMessage());
            $this->failedRow = $this->rowNumber;
            Log::error("Debug: Insert failed at row {$this->rowNumber}: " . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function processSingleUpdate(array $data)
    {
        Log::info("Debug: Attempting to update row {$this->rowNumber}: " . json_encode($data));
        try {
            $affected = DB::table('inflasi')
                ->where('inflasi_id', $data['inflasi_id'])
                ->update([
                    'inflasi' => $data['inflasi'],
                    'andil' => $data['andil'],
                    'updated_at' => $data['updated_at']
                ]);
            if ($affected > 0) {
                $this->updatedCount++;
                Log::info("Debug: Successfully updated row {$this->rowNumber}");
            } else {
                Log::warning("Debug: No rows updated for inflasi_id {$data['inflasi_id']}");
            }
        } catch (\Exception $e) {
            $this->errors->add("row_{$this->rowNumber}", "Update failed: " . $e->getMessage());
            $this->failedRow = $this->rowNumber;
            Log::error("Debug: Update failed at row {$this->rowNumber}: " . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function processBulk(array $inserts, array $updates)
    {
        Log::info("Bulk processing: " . count($inserts) . " inserts, " . count($updates) . " updates");
        DB::beginTransaction();
        try {
            if (!empty($inserts)) {
                Log::info("Inserting " . count($inserts) . " rows");
                Inflasi::insert($inserts);
                $this->insertedCount += count($inserts);
                Log::info("Inserted {$this->insertedCount} rows");
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
                }
                Log::info("Updated total {$this->updatedCount} rows");
            }
            DB::commit();
            Log::info("Bulk transaction committed");
            if (count($inserts) + count($updates) < $this->chunkSize()) {
                $this->stopAfterSmallChunk = true;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors->add("row_{$this->rowNumber}", "Bulk error: " . $e->getMessage());
            $this->failedRow = $this->rowNumber;
            Log::error("Bulk error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
