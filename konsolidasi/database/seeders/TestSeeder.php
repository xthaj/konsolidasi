<?php

namespace Database\Seeders;

use App\Models\Inflasi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        DB::enableQueryLog();
        $months = [
            ['bulan' => 11, 'tahun' => 2024],
            ['bulan' => 12, 'tahun' => 2024],
            ['bulan' => 1, 'tahun' => 2025],
            ['bulan' => 2, 'tahun' => 2025],
            ['bulan' => 3, 'tahun' => 2025],
        ];

        $bulan_tahun_ids = [];
        foreach ($months as $month) {
            $id = DB::table('bulan_tahun')->insertGetId([
                'bulan' => $month['bulan'],
                'tahun' => $month['tahun'],
                'aktif' => ($month['bulan'] == 2 && $month['tahun'] == 2024) ? 1 : 0,
            ]);
            $bulan_tahun_ids[] = $id;
        }
    }
}
