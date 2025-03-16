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
        Log::info("Starting import process, total rows: " . count($rows));

        if ($this->failedRow !== null) {
            Log::warning("Skipping chunk because of a previous failure at row {$this->failedRow}");
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
            if (!isset($row['inflasi']) || !is_numeric($row['inflasi'])) {
                $this->errors->add("row_{$this->rowNumber}", "Inflasi harus numerik");
                $this->failedRow = $this->rowNumber;
                Log::error("Invalid inflasi at row {$this->rowNumber}: " . json_encode($row['inflasi'] ?? 'null'));
                break;
            }

            // Validation: andil
            $andil = isset($row['andil']) && $row['andil'] !== '' ? (float) $row['andil'] : null;
            if (isset($row['andil']) && $row['andil'] !== '' && !is_numeric($row['andil'])) {
                $this->errors->add("row_{$this->rowNumber}", "Andil harus numerik (jika ada)");
                $this->failedRow = $this->rowNumber;
                Log::error("Invalid andil at row {$this->rowNumber}: " . json_encode($row['andil']));
                break;
            }

            // Check for duplicate key in the file
            $key = "{$kd_komoditas}-{$kd_wilayah}";
            if (isset($this->seenKeys[$key])) {
                $this->errors->add("row_{$this->rowNumber}", "Duplikat ditemukan: kombinasi kd_komoditas '$kd_komoditas' dan kd_wilayah '$kd_wilayah' sudah ada di baris sebelumnya.");
                $this->failedRow = $this->rowNumber;
                Log::error("Duplicate key detected: {$key} at row {$this->rowNumber}");
                break;
            }
            $this->seenKeys[$key] = true; // Mark key as seen

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
        }

        if ($this->failedRow === null && (!empty($updates) || !empty($inserts))) {
            DB::beginTransaction();
            try {
                if (!empty($inserts)) {
                    foreach (array_chunk($inserts, 100) as $chunk) {
                        Inflasi::insert($chunk);
                        $this->insertedCount += count($chunk);
                    }
                }
                if (!empty($updates)) {
                    foreach (array_chunk($updates, 100) as $chunk) {
                        foreach ($chunk as $update) {
                            DB::table('inflasi')
                                ->where('inflasi_id', $update['inflasi_id'])
                                ->update([
                                    'inflasi' => $update['inflasi'],
                                    'andil' => $update['andil'],
                                    'updated_at' => $update['updated_at']
                                ]);
                        }
                        $this->updatedCount += count($chunk);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->errors->add("row_{$this->rowNumber}", "Database error: " . $e->getMessage());
                $this->failedRow = $this->rowNumber;
                Log::error("Database error at row {$this->rowNumber}: " . $e->getMessage());
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
