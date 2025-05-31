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

        $bulan = $bulanTahun->bulan;
        $tahun = $bulanTahun->tahun;

        return Inflasi::where('inflasi.bulan_tahun_id', $bulanTahun->bulan_tahun_id)
            ->where('inflasi.kd_level', $this->level)
            ->join('wilayah', 'inflasi.kd_wilayah', '=', 'wilayah.kd_wilayah')
            ->join('komoditas', 'inflasi.kd_komoditas', '=', 'komoditas.kd_komoditas')
            ->leftJoin('rekonsiliasi', 'inflasi.inflasi_id', '=', 'rekonsiliasi.inflasi_id')
            ->select(
                'inflasi.kd_komoditas',
                'komoditas.nama_komoditas',
                'inflasi.kd_wilayah',
                'wilayah.nama_wilayah',
                'inflasi.nilai_inflasi',
                'inflasi.final_inflasi',
                'inflasi.andil',
                'inflasi.final_andil',
                'rekonsiliasi.alasan',
                'rekonsiliasi.detail'
            )
            ->get()
            ->map(function ($row) use ($bulan, $tahun) {
                return [
                    $tahun,
                    $bulan,
                    str_pad((string)$row->kd_komoditas, 3, '0', STR_PAD_LEFT),
                    $row->nama_komoditas,
                    $row->kd_wilayah,
                    $row->nama_wilayah,
                    $row->nilai_inflasi,
                    $row->final_inflasi,
                    $row->andil,
                    $row->final_andil,
                    $row->alasan,
                    $row->detail,
                ];
            });
    }



    public function headings(): array
    {
        return [
            'Tahun',
            'Bulan',
            'Kode Komoditas',
            'Nama Komoditas',
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
