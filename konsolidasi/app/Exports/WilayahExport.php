<?php

namespace App\Exports;

use App\Models\Wilayah;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WilayahExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Wilayah::all();
    }

    /**
     * Define the headings for the Excel file.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'kd_wilayah',
            'Nama Wilayah',
        ];
    }

    public function map($wilayah): array
    {
        return [
            $wilayah->kd_wilayah,
            $wilayah->nama_wilayah,
        ];
    }
}
