<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\Wilayah;
use App\Models\Komoditas;
use App\Models\BulanTahun;
use App\Models\Inflasi;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FinalImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private $validKdWilayah;
    private $validKdKomoditas;
    private $bulanTahunId;
    private $errors;
    private $rowNumber;
    private $level;
    private $existingInflasi;
    private $updatedCount = 0;
    private $insertedCount = 0; // New property to track inserted records
    private $failedRow = null;
    private $seenKeys = [];
    private $stopAfterSmallChunk = false;
    private $debugMode = false;

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
        $inserts = []; // New array for inserts (if needed)

        foreach ($rows as $row) {
            $this->rowNumber++;
            $rowData = $row->toArray();
            Log::info("Processing row {$this->rowNumber}: " . json_encode($rowData));

            if (empty(array_filter($rowData, fn($value) => $value !== null && $value !== ''))) {
                Log::info("Detected end of file at row {$this->rowNumber}");
                $this->stopAfterSmallChunk = true;
                break;
            }

            $kd_wilayah = trim($row['kd_wilayah'] ?? '0');
            if ($kd_wilayah === '') {
                $kd_wilayah = '0';
            }
            if (!in_array($kd_wilayah, $this->validKdWilayah)) {
                $this->errors->add("row_{$this->rowNumber}", "kd_wilayah '$kd_wilayah' tidak valid");
                $this->failedRow = $this->rowNumber;
                break;
            }

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

            $finalInflasiRaw = trim($row['inflasi'] ?? '');
            if ($finalInflasiRaw === '' || !is_numeric($finalInflasiRaw)) {
                $this->errors->add("row_{$this->rowNumber}", "final_inflasi harus numerik");
                $this->failedRow = $this->rowNumber;
                Log::error("Invalid final_inflasi at row {$this->rowNumber}: " . json_encode($finalInflasiRaw));
                break;
            }
            $finalInflasiClean = str_replace(',', '.', $finalInflasiRaw);
            if (!is_numeric($finalInflasiClean)) {
                $this->errors->add("row_{$this->rowNumber}", "final_inflasi tidak valid setelah normalisasi");
                $this->failedRow = $this->rowNumber;
                break;
            }
            $finalInflasi = round((float) $finalInflasiClean, 2);

            $finalAndilRaw = trim($row['andil'] ?? '');
            $finalAndil = null;
            if ($finalAndilRaw !== '') {
                if (!is_numeric($finalAndilRaw)) {
                    $this->errors->add("row_{$this->rowNumber}", "final_andil harus numerik");
                    $this->failedRow = $this->rowNumber;
                    Log::error("Invalid final_andil at row {$this->rowNumber}: " . json_encode($finalAndilRaw));
                    break;
                }
                $finalAndilClean = str_replace(',', '.', $finalAndilRaw);
                if (!is_numeric($finalAndilClean)) {
                    $this->errors->add("row_{$this->rowNumber}", "final_andil tidak valid setelah normalisasi");
                    $this->failedRow = $this->rowNumber;
                    break;
                }
                $finalAndil = round((float) $finalAndilClean, 2);
            }

            $key = "{$kd_komoditas}-{$kd_wilayah}";
            if (isset($this->seenKeys[$key])) {
                $this->errors->add("row_{$this->rowNumber}", "Duplikat: $key sudah ada");
                $this->failedRow = $this->rowNumber;
                break;
            }
            $this->seenKeys[$key] = true;

            // Check if record exists
            if (!isset($this->existingInflasi[$key])) {
                // Optionally insert new record (if allowed)
                $inserts[] = [
                    'bulan_tahun_id' => $this->bulanTahunId,
                    'kd_level' => $this->level,
                    'kd_komoditas' => $kd_komoditas,
                    'kd_wilayah' => $kd_wilayah,
                    'final_inflasi' => $finalInflasi,
                    'final_andil' => $finalAndil,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } else {
                // Update existing record
                $data = [
                    'inflasi_id' => $this->existingInflasi[$key]->inflasi_id,
                    'final_inflasi' => $finalInflasi,
                    'final_andil' => $finalAndil,
                    'updated_at' => now(),
                ];
                $updates[] = $data;
            }

            if ($this->debugMode && $this->failedRow === null) {
                if (!empty($updates)) {
                    $this->processSingleUpdate(end($updates));
                    $updates = [];
                }
                if (!empty($inserts)) {
                    $this->processSingleInsert(end($inserts));
                    $inserts = [];
                }
            }
        }

        if (!$this->debugMode && $this->failedRow === null) {
            if (!empty($updates)) {
                $this->processBulk($updates);
            }
            if (!empty($inserts)) {
                $this->processBulkInserts($inserts);
            }
        }
    }

    private function processSingleUpdate(array $data)
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
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function processBulk(array $updates)
    {
        Log::info("Bulk processing: " . count($updates) . " updates");
        DB::beginTransaction();
        try {
            foreach ($updates as $update) {
                $affected = DB::table('inflasi')
                    ->where('inflasi_id', $update['inflasi_id'])
                    ->update([
                        'final_inflasi' => $update['final_inflasi'],
                        'final_andil' => $update['final_andil'],
                        'updated_at' => $update['updated_at'],
                    ]);
                $this->updatedCount += $affected;
            }
            DB::commit();
            Log::info("Bulk transaction committed, updated {$this->updatedCount} rows");
            if (count($updates) < $this->chunkSize()) {
                $this->stopAfterSmallChunk = true;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors->add("row_{$this->rowNumber}", "Bulk error: " . $e->getMessage());
            $this->failedRow = $this->rowNumber;
            Log::error("Bulk error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    private function processBulkInserts(array $inserts)
    {
        Log::info("Bulk inserting: " . count($inserts) . " records");
        DB::beginTransaction();
        try {
            DB::table('inflasi')->insert($inserts);
            $this->insertedCount += count($inserts);
            DB::commit();
            Log::info("Bulk insert transaction committed, inserted {$this->insertedCount} rows");
            if (count($inserts) < $this->chunkSize()) {
                $this->stopAfterSmallChunk = true;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors->add("row_{$this->rowNumber}", "Bulk insert error: " . $e->getMessage());
            $this->failedRow = $this->rowNumber;
            Log::error("Bulk insert error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
            'inserted' => $this->insertedCount, // Added inserted count
            'failed_row' => $this->failedRow,
        ];
    }
}
