<?php

namespace App\Exports;

use App\Models\BulanTahun;
use App\Models\Inflasi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InflasiExport implements FromCollection, WithHeadings
{
    protected $bulan;
    protected $tahun;
    protected $level;

    public function __construct($bulan, $tahun, $level)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->level = $level;
    }

    public function collection()
    {
        $bulanTahun = BulanTahun::where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->first();

        if (!$bulanTahun) {
            return collect([]);
        }

        return Inflasi::where('inflasi.bulan_tahun_id', $bulanTahun->bulan_tahun_id)
            ->where('inflasi.kd_level', $this->level)
            ->join('wilayah', 'inflasi.kd_wilayah', '=', 'wilayah.kd_wilayah')
            ->leftJoin('rekonsiliasi', 'inflasi.inflasi_id', '=', 'rekonsiliasi.inflasi_id')
            ->select(
                'inflasi.kd_komoditas',
                'inflasi.kd_wilayah',
                'wilayah.nama_wilayah',
                'inflasi.inflasi',
                'inflasi.final_inflasi',
                'inflasi.andil',
                'inflasi.final_andil',
                'rekonsiliasi.alasan',
                'rekonsiliasi.detail'
            )
            ->get();
    }

    public function headings(): array
    {
        return [
            'Kode Komoditas',
            'Kode Wilayah',
            'Nama Wilayah',
            'Inflasi',
            'Final Inflasi',
            'Andil',
            'Final Andil',
            'Alasan',
            'Detail',
        ];
    }
}
