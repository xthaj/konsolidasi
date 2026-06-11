<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InflasiMultiSheetExport implements WithMultipleSheets
{
    protected $bulan;
    protected $tahun;
    protected $levels;

    public function __construct($bulan, $tahun, array $levels)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->levels = $levels;
    }

    public function sheets(): array
    {
        $levelNames = [
            '01' => 'HK',
            '02' => 'HKD',
            '03' => 'HPB',
            '04' => 'HPed',
            '05' => 'HP',
        ];

        $sheets = [];
        foreach ($this->levels as $level) {
            $sheet = new InflasiExport($this->bulan, $this->tahun, $level);
            $sheet->setSheetName($levelNames[$level] ?? "Level {$level}");
            $sheets[] = $sheet;
        }
        return $sheets;
    }
}
