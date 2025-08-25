<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RekonsiliasiMultiLevelExport implements WithMultipleSheets
{
    protected $bulan, $tahun;

    protected $levels = [
        '01' => 'Harga Konsumen Kota',
        '02' => 'Harga Konsumen Desa',
        '03' => 'Harga Perdagangan Besar',
        '04' => 'Harga Produsen Desa',
        '05' => 'Harga Produsen',
    ];

    public function __construct($bulan, $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function sheets(): array
    {
        return array_map(
            fn($code, $title) => new RekonsiliasiLevelSheet($this->bulan, $this->tahun, $code, $title),
            array_keys($this->levels),
            $this->levels
        );
    }
}
