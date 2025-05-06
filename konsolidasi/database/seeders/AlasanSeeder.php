<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlasanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $alasan = [
            "Kondisi Alam",
            "Masa Panen",
            "Gagal Panen",
            "Promo dan Diskon",
            "Harga Stok Melimpah",
            "Stok Menipis/Langka",
            "Harga Kembali Normal",
            "Turun Harga dari Distributor",
            "Kenaikan Harga dari Distributor",
            "Perbedaan Kualitas",
            "Supplier Menaikkan Harga",
            "Supplier Menurunkan Harga",
            "Persaingan Harga",
            "Permintaan Meningkat",
            "Permintaan Menurun",
            "Operasi Pasar",
            "Kebijakan Pemerintah Pusat",
            "Kebijakan Pemerintah Daerah",
            "Kesalahan Petugas Mencacah",
            "Penurunan Produksi",
            "Kenaikan Produksi",
            "Salah Entri Data",
            "Penggantian Responden",
            "Lainnya",
        ];

        foreach ($alasan as $item) {
            DB::table('alasan')->insert(['keterangan' => $item]);
        }
    }
}
