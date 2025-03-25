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

        $komoditas_list = ['000', '016'];
        $wilayah_list = [
            0,
            11,
            1106,
            1107,
            1114,
            1171,
            1174,
            12,
            1207,
            1211,
            1212,
            1271,
            1273,
            1275,
            1277,
            1278,
            13,
            1311,
            1312,
            1371,
            1375,
            14,
            1403,
            1406,
            1471,
            1473,
            15,
            1501,
            1509,
            1571,
            16,
            1602,
            1603,
            1671,
            1674,
            17,
            1706,
            1771,
            18,
            1804,
            1811,
            1871,
            1872,
            19,
            1902,
            1903,
            1906,
            1971,
            21,
            2101,
            2171,
            2172,
            31,
            3100,
            32,
            3204,
            3210,
            3213,
            3271,
            3272,
            3273,
            3274,
            3275,
            3276,
            3278,
            33,
            3301,
            3302,
            3307,
            3312,
            3317,
            3319,
            3372,
            3374,
            3376,
            34,
            3403,
            3471,
            35,
            3504,
            3509,
            3510,
            3522,
            3525,
            3529,
            3571,
            3573,
            3574,
            3577,
            3578,
            36,
            3601,
            3602,
            3671,
            3672,
            3673,
            51,
            5102,
            5103,
            5108,
            5171,
            52,
            5204,
            5271,
            5272,
            53,
            5302,
            5304,
            5310,
            5312,
            5371,
            61,
            6106,
            6107,
            6111,
            6171,
            6172,
            62,
            6202,
            6203,
            6206,
            6271,
            63,
            6301,
            6302,
            6307,
            6309,
            6371,
            64,
            6405,
            6409,
            6471,
            6472,
            65,
            6502,
            6504,
            6571,
            71,
            7105,
            7106,
            7171,
            7174,
            72,
            7202,
            7203,
            7206,
            7271,
            73,
            7302,
            7311,
            7313,
            7314,
            7325,
            7371,
            7372,
            7373,
            74,
            7403,
            7404,
            7471,
            7472,
            75,
            7502,
            7571,
            76,
            7601,
            7604,
            81,
            8103,
            8171,
            8172,
            82,
            8202,
            8271,
            91,
            9105,
            92,
            9202,
            9203,
            9271,
            94,
            9471,
            95,
            9501,
            96,
            9601,
            9604,
            97,
            9702
        ];
        $levels = ['01', '02', '03', '04', '05'];

        $data = [];

        // foreach ($bulan_tahun_ids as $bulan_tahun_id) {
        //     foreach ($komoditas_list as $kd_komoditas) {
        //         foreach ($wilayah_list as $kd_wilayah) {
        //             foreach ($levels as $kd_level) {
        //                 $data[] = [
        //                     'kd_komoditas' => (string) $kd_komoditas,
        //                     'kd_wilayah' => (string) $kd_wilayah,
        //                     'bulan_tahun_id' => $bulan_tahun_id,
        //                     'kd_level' => (string) $kd_level,
        //                     'inflasi' => (float) mt_rand(-500, 500) / 100,
        //                     'andil' => (float) mt_rand(0, 10) / 10,
        //                     'created_at' => now(),
        //                     'updated_at' => now(),
        //                 ];
        //             }
        //         }
        //     }
        // }

        // foreach (array_chunk($data, 100) as $chunk) {
        //     Inflasi::insert($chunk);
        // }

        // Get and log query log
        $queries = DB::getQueryLog();
        Log::info('SQL Queries:', $queries);

        // Optional: To view the queries immediately
        dd($queries);
    }
}
