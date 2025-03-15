<?php

namespace App\Exports;

use App\Models\Komoditas;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class KomoditasExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Return the collection of Komoditas.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Komoditas::all();
    }

    /**
     * Define the headings for the Excel file.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'kd_komoditas',
            'Nama Komoditas',
        ];
    }

    /**
     * Map the data for each row.
     *
     * @param  \App\Models\Komoditas  $komoditas
     * @return array
     */
    public function map($komoditas): array
    {
        return [
            $komoditas->kd_komoditas,
            $komoditas->nama_komoditas,
        ];
    }
}
