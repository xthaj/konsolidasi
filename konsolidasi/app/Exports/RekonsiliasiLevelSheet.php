<?php

namespace App\Exports;

use App\Models\BulanTahun;
use App\Models\LevelHarga;
use App\Models\Rekonsiliasi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;


class RekonsiliasiLevelSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $bulan;
    protected $tahun;
    protected $level;
    protected $title;

    public function __construct($bulan, $tahun, $level, $title)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->level = $level;
        $this->title = $title;
    }

    public function collection()
    {
        $bulanTahun = BulanTahun::where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->firstOrFail();

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
            ->get()
            ->map(fn($row) => [
                $this->tahun,
                $this->bulan,
                str_pad((string)$row->kd_komoditas, 3, '0', STR_PAD_LEFT),
                $row->nama_komoditas,
                $row->kd_wilayah,
                $row->nama_wilayah,
                $row->nilai_inflasi,
                $row->andil,
                $row->alasan,
                $row->detail,
            ]);
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

    public function title(): string
    {
        return LevelHarga::getLevelHargaNameShortened($this->level);
    }
}
