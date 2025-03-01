<?php

namespace App\Imports;

use App\Models\Inflasi;
use App\Models\Wilayah;
use App\Models\Komoditas;
use App\Models\BulanTahun;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\MessageBag;

class DataImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    private $validKdWilayah;
    private $validKdKomoditas;
    private $bulanTahunId;
    private $errors;
    private $rowNumber;
    private $level;

    public function __construct($bulan, $tahun, $level)
    {
        // store valid kd wilayah and kd komoditas
        $this->validKdWilayah = Wilayah::pluck('kd_wilayah')->toArray();
        $this->validKdKomoditas = Komoditas::pluck('kd_komoditas')->toArray();

        // error and row counter
        $this->errors = new MessageBag();
        $this->rowNumber = 1; // Start from 1 (header row)

        // Store level from constructor
        $this->level = $level;

        // Check if BulanTahun record exists
        $bulanTahun = BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->first();

        if ($bulanTahun) {
            // If exists, use the existing ID
            $this->bulanTahunId = $bulanTahun->bulan_tahun_id;
        } else {
            // If not, create a new record and use the new ID
            $bulanTahun = BulanTahun::create([
                'bulan' => $bulan,
                'tahun' => $tahun,
                'aktif' => 0]
            );
            $this->bulanTahunId = $bulanTahun->bulan_tahun_id;
        }
    }

    public function model(array $row)
    {
        $this->rowNumber++;


        // Normalize kd_wilayah
        $kd_wilayah = $row['kd_wilayah'] ?? '0';
        if (is_null($kd_wilayah) || $kd_wilayah === '') {
            $kd_wilayah = '0';
        }

        // Validate kd_wilayah
        if (!in_array($kd_wilayah, $this->validKdWilayah)) {
            $this->errors->add("row_{$this->rowNumber}", "Row {$this->rowNumber}: The kd_wilayah '$kd_wilayah' is not a valid region code.");
            return null; // Skip this row
        }

        // Normalize kd_komoditas to 3 digits with leading zeros
        $kd_komoditas = str_pad((string) $row['kd_komoditas'], 3, '0', STR_PAD_LEFT);

        // Validate kd_komoditas
        if (!in_array($kd_komoditas, $this->validKdKomoditas)) {
            $this->errors->add("row_{$this->rowNumber}", "Row {$this->rowNumber}: The kd_komoditas '{$row['kd_komoditas']}' (normalized to '$kd_komoditas') is not a valid commodity code.");
            return null; // Skip this row
        }

        // Validate andil based on kd_wilayah
        $andil = isset($row['andil']) && $row['andil'] !== '' ? (float) $row['andil'] : null;
        if ($kd_wilayah === '0' && is_null($andil)) {
            $this->errors->add("row_{$this->rowNumber}", "Row {$this->rowNumber}: The andil must not be null when kd_wilayah is '0'.");
            return null; // Skip this row
        }

        // Validate inflasi (maps to harga)
        if (!isset($row['inflasi']) || !is_numeric($row['inflasi'])) {
            $this->errors->add("row_{$this->rowNumber}", "Row {$this->rowNumber}: The inflasi must be a numeric value.");
            return null; // Skip this row
        }

        return new Inflasi([
            'kd_wilayah' => (string) $kd_wilayah,
            'kd_komoditas' => (string) $kd_komoditas,
            'bulan_tahun_id' => $this->bulanTahunId,
            'kd_level' => (string) $this->level,
            'harga' => (float) $row['inflasi'], // Map inflasi to harga
            'andil' => $andil,
        ]);
    }

    public function rules(): array
    {
        // Basic validation rules for the Excel columns
        return [
            'kd_wilayah' => 'nullable', // Validation moved to model() for better error handling
            'kd_komoditas' => 'required', // Validation moved to model()
            'inflasi' => 'required|numeric',
            'andil' => 'nullable|numeric', // Validation for kd_wilayah = '0' moved to model()
        ];
    }

    public function batchSize(): int
    {
        return 100; // Insert 1000 rows at a time
    }

    public function chunkSize(): int
    {
        return 100; // Process 1000 rows per chunk
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
