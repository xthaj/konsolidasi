<?php

namespace App\Exports;

use App\Models\BulanTahun;
use App\Models\Rekonsiliasi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RekonsiliasiExport implements FromCollection, WithHeadings
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

        return Rekonsiliasi::join('inflasi', 'rekonsiliasi.inflasi_id', '=', 'inflasi.inflasi_id')
            ->join('wilayah', 'inflasi.kd_wilayah', '=', 'wilayah.kd_wilayah')
            ->join('komoditas', 'inflasi.kd_komoditas', '=', 'komoditas.kd_komoditas')
            ->where('inflasi.bulan_tahun_id', $bulanTahun->bulan_tahun_id)
            ->where('inflasi.kd_level', $this->level)
            ->select(
                'inflasi.kd_komoditas',
                'komoditas.nama_komoditas',
                'inflasi.kd_wilayah',
                'wilayah.nama_wilayah',
                'inflasi.nilai_inflasi',
                'inflasi.andil',
                'rekonsiliasi.alasan',
                'rekonsiliasi.detail'
            )
            ->orderBy('inflasi.kd_wilayah')
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
                    $row->andil,
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
            'Andil',
            'Alasan',
            'Detail',
        ];
    }
}
