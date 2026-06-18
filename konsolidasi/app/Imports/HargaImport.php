<?php

namespace App\Imports;

use App\Models\BulanTahun;
use App\Models\Inflasi;
use App\Models\Komoditas;
use App\Models\Wilayah;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;

class HargaImport implements ToCollection, WithHeadingRow, WithChunkReading, WithEvents
{
    private array $validKdWilayah;
    private array $validKdKomoditas;
    private int $bulanTahunId;
    private MessageBag $errors;
    private int $rowNumber = 1;
    private string $level;
    private Collection $existingInflasi;
    private int $updatedCount = 0;
    private ?int $failedRow = null;
    private array $seenKeys = [];
    private bool $stopProcessing = false;
    private const CHUNK_SIZE = 200;
    private float $startTime;
    private float $maxExecutionTime;

    public function __construct(int $bulan, int $tahun, string $level)
    {
        $this->maxExecutionTime = (float) ini_get('max_execution_time') ?: 30;
        $this->startTime = microtime(true);

        $this->validKdWilayah = Wilayah::pluck('kd_wilayah')->toArray();
        $this->validKdKomoditas = Komoditas::pluck('kd_komoditas')->toArray();
        $this->errors = new MessageBag();
        $this->level = $level;
        $this->initializeBulanTahun($bulan, $tahun);
        $this->loadExistingInflasi();
    }

    private function checkExecutionTime(): void
    {
        $elapsedTime = microtime(true) - $this->startTime;
        $bufferTime = 5;
        if ($elapsedTime >= ($this->maxExecutionTime - $bufferTime)) {
            $this->errors->add("row_{$this->rowNumber}", "Proses telah berjalan melebihi batas waktu maksimum.");
            $this->failedRow = $this->rowNumber;
            $this->stopProcessing = true;
        }
    }

    private function initializeBulanTahun(int $bulan, int $tahun): void
    {
        $bulanTahun = BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->orderBy('bulan_tahun_id', 'asc')->first();
        if (!$bulanTahun) {
            throw new \Exception("Periode bulan {$bulan} tahun {$tahun} tidak ditemukan.");
        }
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
        if ($this->stopProcessing) return;

        $this->checkExecutionTime();
        $updates = [];

        foreach ($rows as $row) {
            $this->rowNumber++;
            $this->checkExecutionTime();

            try {
                $this->validateAndPrepareRow($row, $updates);
            } catch (\Exception $e) {
                $this->failedRow = $this->rowNumber;
                $this->stopProcessing = true;
                break;
            }

            if ($this->stopProcessing) break;
        }

        if (!empty($updates)) {
            try {
                $this->processBulk($updates);
            } catch (\Exception $e) {
                $this->errors->add("bulk_error", "Gagal memproses data: " . $e->getMessage());
                $this->failedRow = $this->rowNumber;
                $this->stopProcessing = true;
            }
        }
    }

    private function validateAndPrepareRow(Collection $row, array &$updates): void
    {
        $kd_wilayah = trim($row['kd_wilayah'] ?? '');
        if ($kd_wilayah === '') {
            $this->stopProcessing = true;
            return;
        }

        $kd_komoditasRaw = trim($row['kd_komoditas'] ?? '');
        $hargaRaw = trim($row['harga'] ?? '');
        $rentangBawahRaw = trim($row['rentang_bawah'] ?? '');
        $rentangAtasRaw = trim($row['rentang_atas'] ?? '');

        if (!in_array($kd_wilayah, $this->validKdWilayah)) {
            $this->throwError("kd_wilayah '$kd_wilayah' tidak valid");
        }

        if (is_null($kd_komoditasRaw) || $kd_komoditasRaw === '') {
            $this->throwError('kd_komoditas kosong');
        }
        if (!is_numeric($kd_komoditasRaw)) {
            $this->throwError("kd_komoditas '$kd_komoditasRaw' harus berupa bilangan bulat");
        }
        $kd_komoditas = (int) $kd_komoditasRaw;
        if (!in_array($kd_komoditas, $this->validKdKomoditas, true)) {
            $this->throwError("kd_komoditas '$kd_komoditas' tidak valid atau tidak ditemukan");
        }

        $hargaClean = str_replace(',', '.', $hargaRaw);
        if ($hargaRaw === '' || !is_numeric($hargaClean)) {
            $this->throwError('harga harus numerik');
        }
        $harga = (int) round((float) $hargaClean);
        if ($harga < 0) {
            $this->throwError('harga tidak boleh negatif');
        }

        $rentangBawah = null;
        if ($rentangBawahRaw !== '') {
            $rbClean = str_replace(',', '.', $rentangBawahRaw);
            if (!is_numeric($rbClean)) {
                $this->throwError('rentang_bawah harus numerik');
            }
            $rentangBawah = (int) round((float) $rbClean);
            if ($rentangBawah < 0) {
                $this->throwError('rentang_bawah tidak boleh negatif');
            }
        }

        $rentangAtas = null;
        if ($rentangAtasRaw !== '') {
            $raClean = str_replace(',', '.', $rentangAtasRaw);
            if (!is_numeric($raClean)) {
                $this->throwError('rentang_atas harus numerik');
            }
            $rentangAtas = (int) round((float) $raClean);
            if ($rentangAtas < 0) {
                $this->throwError('rentang_atas tidak boleh negatif');
            }
        }

        $key = "{$kd_komoditas}-{$kd_wilayah}";
        if (isset($this->seenKeys[$key])) {
            $this->throwError("Duplikat: kombinasi kd_komoditas-kd_wilayah $key sudah ada");
        }
        $this->seenKeys[$key] = true;

        if (!isset($this->existingInflasi[$key])) {
            $this->throwError("Tidak ada data inflasi untuk kombinasi kd_komoditas-kd_wilayah {$key}. Harap upload data harmonisasi terlebih dahulu.");
        }

        $updates[] = [
            'inflasi_id' => $this->existingInflasi[$key]->inflasi_id,
            'harga' => $harga,
            'rentang_bawah' => $rentangBawah,
            'rentang_atas' => $rentangAtas,
            'updated_at' => now(),
        ];
    }

    private function throwError(string $message): void
    {
        $this->errors->add("row_{$this->rowNumber}", $message);
        $this->failedRow = $this->rowNumber;
        throw new Exception("Kegagalan di baris {$this->rowNumber}: $message");
    }

    private function processBulk(array $updates): void
    {
        DB::beginTransaction();
        try {
            $updatedRows = 0;
            foreach ($updates as $update) {
                $affected = DB::table('inflasi')
                    ->where('inflasi_id', $update['inflasi_id'])
                    ->update([
                        'harga' => $update['harga'],
                        'rentang_bawah' => $update['rentang_bawah'],
                        'rentang_atas' => $update['rentang_atas'],
                        'updated_at' => $update['updated_at'],
                    ]);
                $updatedRows += $affected;
            }
            $this->updatedCount += $updatedRows;

            DB::commit();

            if (count($updates) < self::CHUNK_SIZE) {
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
            'failed_row' => $this->failedRow,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                Log::info('Harga import completed', [
                    'timestamp' => now(),
                    'summary' => $this->getSummary()
                ]);
            }
        ];
    }
}
