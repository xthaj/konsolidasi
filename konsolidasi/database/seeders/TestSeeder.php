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
        $wilayah_list = [0, 11, 1106, 1107, 1114, 1171, 1174, 12, 1207, 1211, 1212, 1271, 1273,
            1275, 1277, 1278, 13, 1311, 1312, 1371, 1375, 14, 1403, 1406, 1471, 1473,
            15, 1501, 1509, 1571, 16, 1602, 1603, 1671, 1674, 17, 1706, 1771, 18,
            1804, 1811, 1871, 1872, 19, 1902, 1903, 1906, 1971, 21, 2101, 2171, 2172,
            31, 3100, 32, 3204, 3210, 3213, 3271, 3272, 3273, 3274, 3275, 3276, 3278,
            33, 3301, 3302, 3307, 3312, 3317, 3319, 3372, 3374, 3376, 34, 3403, 3471,
            35, 3504, 3509, 3510, 3522, 3525, 3529, 3571, 3573, 3574, 3577, 3578, 36,
            3601, 3602, 3671, 3672, 3673, 51, 5102, 5103, 5108, 5171, 52, 5204, 5271,
            5272, 53, 5302, 5304, 5310, 5312, 5371, 61, 6106, 6107, 6111, 6171, 6172,
            62, 6202, 6203, 6206, 6271, 63, 6301, 6302, 6307, 6309, 6371, 64, 6405,
            6409, 6471, 6472, 65, 6502, 6504, 6571, 71, 7105, 7106, 7171, 7174, 72,
            7202, 7203, 7206, 7271, 73, 7302, 7311, 7313, 7314, 7325, 7371, 7372, 7373,
            74, 7403, 7404, 7471, 7472, 75, 7502, 7571, 76, 7601, 7604, 81, 8103,
            8171, 8172, 82, 8202, 8271, 91, 9105, 92, 9202, 9203, 9271, 94, 9471, 95,
            9501, 96, 9601, 9604, 97, 9702
        ];
        $levels = ['01', '02', '03', '04', '05'];

        $data = [];

        foreach ($bulan_tahun_ids as $bulan_tahun_id) {
            foreach ($komoditas_list as $kd_komoditas) {
                foreach ($wilayah_list as $kd_wilayah) {
                    foreach ($levels as $kd_level) {
                        $data[] = [
                            'kd_komoditas' => (string) $kd_komoditas,
                            'kd_wilayah' => (string) $kd_wilayah,
                            'bulan_tahun_id' => $bulan_tahun_id,
                            'kd_level' => (string) $kd_level,
                            'harga' => (float) mt_rand(-500, 500) / 100,
                            'andil' => (float) mt_rand(0, 10) / 10,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }


        foreach (array_chunk($data, 100) as $chunk) {
            Inflasi::insert($chunk);
        }

        // Get and log query log
        $queries = DB::getQueryLog();
        Log::info('SQL Queries:', $queries);

        // Optional: To view the queries immediately
        dd($queries);
    }
}


        // Insert into 'bulan_tahun' and get the ID
        // $id = DB::table('bulan_tahun')->insertGetId([
        //     'bulan' => 11,
        //     'tahun' => 2024,
        //     'aktif' => 1,
        // ]);



        // foreach(array_chunk($data, 1000) as $chunk) {
        //     Inflasi::insert($chunk);
        // }

        // DB::table('inflasi')->insert([
        //     ['kd_komoditas' => "000", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.08, 'andil' => 0.3],
        //     ['kd_komoditas' => "005", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.08, 'andil' => 0.0],
        //     ['kd_komoditas' => "010", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -2.48, 'andil' => 0.0],
        //     ['kd_komoditas' => "011", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 2.76, 'andil' => 0.0],
        //     ['kd_komoditas' => "012", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.01, 'andil' => 0.0],
        //     ['kd_komoditas' => "014", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.03, 'andil' => 0.0],
        //     ['kd_komoditas' => "016", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.82, 'andil' => 0.0],
        //     ['kd_komoditas' => "017", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 7.94, 'andil' => 0.0],
        //     ['kd_komoditas' => "018", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.7, 'andil' => 0.0],
        //     ['kd_komoditas' => "019", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -9.98, 'andil' => 0.0],
        //     ['kd_komoditas' => "020", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -7.71, 'andil' => 0.0],
        //     ['kd_komoditas' => "021", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 16.93, 'andil' => 0.0],
        //     ['kd_komoditas' => "022", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.4, 'andil' => 0.0],
        //     ['kd_komoditas' => "023", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.11, 'andil' => 0.0],
        //     ['kd_komoditas' => "024", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.78, 'andil' => 0.0],
        //     ['kd_komoditas' => "027", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -1.25, 'andil' => 0.0],
        //     ['kd_komoditas' => "028", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.02, 'andil' => 0.0],
        //     ['kd_komoditas' => "029", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "030", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.08, 'andil' => 0.0],
        //     ['kd_komoditas' => "031", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.04, 'andil' => 0.0],
        //     ['kd_komoditas' => "032", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "033", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.03, 'andil' => 0.0],
        //     ['kd_komoditas' => "034", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.07, 'andil' => 0.0],
        //     ['kd_komoditas' => "035", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.93, 'andil' => 0.0],
        //     ['kd_komoditas' => "036", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "037", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "038", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "039", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.05, 'andil' => 0.0],
        //     ['kd_komoditas' => "040", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "041", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "042", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "043", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.09, 'andil' => 0.0],
        //     ['kd_komoditas' => "044", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.27, 'andil' => 0.0],
        //     ['kd_komoditas' => "045", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.4, 'andil' => 0.0],
        //     ['kd_komoditas' => "046", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.65, 'andil' => 0.0],
        //     ['kd_komoditas' => "047", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 4.44, 'andil' => 0.0],
        //     ['kd_komoditas' => "049", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -1.17, 'andil' => 0.0],
        //     ['kd_komoditas' => "050", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.92, 'andil' => 0.0],
        //     ['kd_komoditas' => "051", 'kd_wilayah' => "0", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "000", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.08, 'andil' => 0.0],
        //     ['kd_komoditas' => "005", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -1.06, 'andil' => 0.0],
        //     ['kd_komoditas' => "010", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -1.88, 'andil' => 0.0],
        //     ['kd_komoditas' => "011", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 5.5, 'andil' => 0.0],
        //     ['kd_komoditas' => "012", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.68, 'andil' => 0.0],
        //     ['kd_komoditas' => "014", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.06, 'andil' => 0.0],
        //     ['kd_komoditas' => "016", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -2.75, 'andil' => 0.0],
        //     ['kd_komoditas' => "017", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 13.87, 'andil' => 0.0],
        //     ['kd_komoditas' => "018", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.22, 'andil' => 0.0],
        //     ['kd_komoditas' => "019", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -7.96, 'andil' => 0.0],
        //     ['kd_komoditas' => "020", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -29.94, 'andil' => 0.0],
        //     ['kd_komoditas' => "021", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 15.82, 'andil' => 0.0],
        //     ['kd_komoditas' => "022", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 2.25, 'andil' => 0.0],
        //     ['kd_komoditas' => "023", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.51, 'andil' => 0.0],
        //     ['kd_komoditas' => "024", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 3.94, 'andil' => 0.0],
        //     ['kd_komoditas' => "027", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 5.98, 'andil' => 0.0],
        //     ['kd_komoditas' => "028", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "030", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "034", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.21, 'andil' => 0.0],
        //     ['kd_komoditas' => "035", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "036", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "037", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "038", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "039", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "040", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "041", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "042", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "043", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "044", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.44, 'andil' => 0.0],
        //     ['kd_komoditas' => "045", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.47, 'andil' => 0.0],
        //     ['kd_komoditas' => "046", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.46, 'andil' => 0.0],
        //     ['kd_komoditas' => "047", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 5.56, 'andil' => 0.0],
        //     ['kd_komoditas' => "049", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -1.37, 'andil' => 0.0],
        //     ['kd_komoditas' => "050", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.4, 'andil' => 0.0],
        //     ['kd_komoditas' => "051", 'kd_wilayah' => "11", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "000", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.13, 'andil' => 0.0],
        //     ['kd_komoditas' => "005", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.34, 'andil' => 0.0],
        //     ['kd_komoditas' => "010", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.46, 'andil' => 0.0],
        //     ['kd_komoditas' => "011", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 9.38, 'andil' => 0.0],
        //     ['kd_komoditas' => "012", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.15, 'andil' => 0.0],
        //     ['kd_komoditas' => "014", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.37, 'andil' => 0.0],
        //     ['kd_komoditas' => "016", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.53, 'andil' => 0.0],
        //     ['kd_komoditas' => "017", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 11.36, 'andil' => 0.0],
        //     ['kd_komoditas' => "018", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 4.58, 'andil' => 0.0],
        //     ['kd_komoditas' => "019", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 8.09, 'andil' => 0.0],
        //     ['kd_komoditas' => "020", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -29.68, 'andil' => 0.0],
        //     ['kd_komoditas' => "021", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 37.7, 'andil' => 0.0],
        //     ['kd_komoditas' => "022", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.25, 'andil' => 0.0],
        //     ['kd_komoditas' => "023", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.1, 'andil' => 0.0],
        //     ['kd_komoditas' => "024", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 2.38, 'andil' => 0.0],
        //     ['kd_komoditas' => "027", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -5.63, 'andil' => 0.0],
        //     ['kd_komoditas' => "028", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "029", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "030", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.2, 'andil' => 0.0],
        //     ['kd_komoditas' => "031", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "033", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.8, 'andil' => 0.0],
        //     ['kd_komoditas' => "034", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -8.59, 'andil' => 0.0],
        //     ['kd_komoditas' => "035", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.66, 'andil' => 0.0],
        //     ['kd_komoditas' => "036", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "037", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "038", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "039", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "040", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "041", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "042", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "043", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "044", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.29, 'andil' => 0.0],
        //     ['kd_komoditas' => "045", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.65, 'andil' => 0.0],
        //     ['kd_komoditas' => "046", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.51, 'andil' => 0.0],
        //     ['kd_komoditas' => "047", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 4.95, 'andil' => 0.0],
        //     ['kd_komoditas' => "049", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.68, 'andil' => 0.0],
        //     ['kd_komoditas' => "050", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.3, 'andil' => 0.0],
        //     ['kd_komoditas' => "051", 'kd_wilayah' => "12", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "000", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.11, 'andil' => 0.0],
        //     ['kd_komoditas' => "005", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.35, 'andil' => 0.0],
        //     ['kd_komoditas' => "011", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 5.43, 'andil' => 0.0],
        //     ['kd_komoditas' => "012", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "014", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "016", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.01, 'andil' => 0.0],
        //     ['kd_komoditas' => "017", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 23.69, 'andil' => 0.0],
        //     ['kd_komoditas' => "018", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.24, 'andil' => 0.0],
        //     ['kd_komoditas' => "019", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -3.71, 'andil' => 0.0],
        //     ['kd_komoditas' => "020", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -30.76, 'andil' => 0.0],
        //     ['kd_komoditas' => "021", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 15.32, 'andil' => 0.0],
        //     ['kd_komoditas' => "022", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.13, 'andil' => 0.0],
        //     ['kd_komoditas' => "023", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.25, 'andil' => 0.0],
        //     ['kd_komoditas' => "024", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.3, 'andil' => 0.0],
        //     ['kd_komoditas' => "027", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.97, 'andil' => 0.0],
        //     ['kd_komoditas' => "028", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "029", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "030", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "031", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "033", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "034", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.24, 'andil' => 0.0],
        //     ['kd_komoditas' => "035", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.63, 'andil' => 0.0],
        //     ['kd_komoditas' => "036", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "037", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "038", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "039", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "040", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "041", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "042", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "043", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.11, 'andil' => 0.0],
        //     ['kd_komoditas' => "044", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.78, 'andil' => 0.0],
        //     ['kd_komoditas' => "045", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.99, 'andil' => 0.0],
        //     ['kd_komoditas' => "046", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.27, 'andil' => 0.0],
        //     ['kd_komoditas' => "047", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 5.47, 'andil' => 0.0],
        //     ['kd_komoditas' => "049", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.88, 'andil' => 0.0],
        //     ['kd_komoditas' => "050", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.24, 'andil' => 0.0],
        //     ['kd_komoditas' => "051", 'kd_wilayah' => "13", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "000", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.06, 'andil' => 0.0],
        //     ['kd_komoditas' => "005", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.1, 'andil' => 0.0],
        //     ['kd_komoditas' => "010", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.89, 'andil' => 0.0],
        //     ['kd_komoditas' => "011", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 7.36, 'andil' => 0.0],
        //     ['kd_komoditas' => "012", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 4.25, 'andil' => 0.0],
        //     ['kd_komoditas' => "014", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "016", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.73, 'andil' => 0.0],
        //     ['kd_komoditas' => "017", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 18.18, 'andil' => 0.0],
        //     ['kd_komoditas' => "018", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.06, 'andil' => 0.0],
        //     ['kd_komoditas' => "019", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -5.99, 'andil' => 0.0],
        //     ['kd_komoditas' => "020", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -15.05, 'andil' => 0.0],
        //     ['kd_komoditas' => "021", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 14.36, 'andil' => 0.0],
        //     ['kd_komoditas' => "022", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.03, 'andil' => 0.0],
        //     ['kd_komoditas' => "023", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.05, 'andil' => 0.0],
        //     ['kd_komoditas' => "024", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.42, 'andil' => 0.0],
        //     ['kd_komoditas' => "027", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -6.5, 'andil' => 0.0],
        //     ['kd_komoditas' => "028", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "029", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "030", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.08, 'andil' => 0.0],
        //     ['kd_komoditas' => "031", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.1, 'andil' => 0.0],
        //     ['kd_komoditas' => "034", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.41, 'andil' => 0.0],
        //     ['kd_komoditas' => "035", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.09, 'andil' => 0.0],
        //     ['kd_komoditas' => "036", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "037", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "039", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "040", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "041", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "042", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "043", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "044", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.1, 'andil' => 0.0],
        //     ['kd_komoditas' => "045", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.97, 'andil' => 0.0],
        //     ['kd_komoditas' => "046", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.48, 'andil' => 0.0],
        //     ['kd_komoditas' => "047", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 3.99, 'andil' => 0.0],
        //     ['kd_komoditas' => "049", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.42, 'andil' => 0.0],
        //     ['kd_komoditas' => "050", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.27, 'andil' => 0.0],
        //     ['kd_komoditas' => "051", 'kd_wilayah' => "14", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "000", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.03, 'andil' => 0.0],
        //     ['kd_komoditas' => "005", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.7, 'andil' => 0.0],
        //     ['kd_komoditas' => "010", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "011", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 16.42, 'andil' => 0.0],
        //     ['kd_komoditas' => "012", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.25, 'andil' => 0.0],
        //     ['kd_komoditas' => "014", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "016", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.02, 'andil' => 0.0],
        //     ['kd_komoditas' => "017", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 16.6, 'andil' => 0.0],
        //     ['kd_komoditas' => "018", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.75, 'andil' => 0.0],
        //     ['kd_komoditas' => "019", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -20.39, 'andil' => 0.0],
        //     ['kd_komoditas' => "020", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -17.78, 'andil' => 0.0],
        //     ['kd_komoditas' => "021", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 6.36, 'andil' => 0.0],
        //     ['kd_komoditas' => "022", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.1, 'andil' => 0.0],
        //     ['kd_komoditas' => "023", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.02, 'andil' => 0.0],
        //     ['kd_komoditas' => "024", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.98, 'andil' => 0.0],
        //     ['kd_komoditas' => "027", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "028", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "030", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -1.24, 'andil' => 0.0],
        //     ['kd_komoditas' => "031", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.35, 'andil' => 0.0],
        //     ['kd_komoditas' => "034", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 7.04, 'andil' => 0.0],
        //     ['kd_komoditas' => "035", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 4.44, 'andil' => 0.0],
        //     ['kd_komoditas' => "036", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "037", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "038", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "039", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "040", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "041", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "042", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "043", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "044", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "045", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.16, 'andil' => 0.0],
        //     ['kd_komoditas' => "046", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.77, 'andil' => 0.0],
        //     ['kd_komoditas' => "047", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 4.5, 'andil' => 0.0],
        //     ['kd_komoditas' => "049", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.95, 'andil' => 0.0],
        //     ['kd_komoditas' => "050", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.6, 'andil' => 0.0],
        //     ['kd_komoditas' => "051", 'kd_wilayah' => "15", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.0, 'andil' => 0.0],
        //     ['kd_komoditas' => "000", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.09, 'andil' => 0.0],
        //     ['kd_komoditas' => "005", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.13, 'andil' => 0.0],
        //     ['kd_komoditas' => "010", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.46, 'andil' => 0.0],
        //     ['kd_komoditas' => "011", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 6.83, 'andil' => 0.0],
        //     ['kd_komoditas' => "012", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -6.09, 'andil' => 0.0],
        //     ['kd_komoditas' => "014", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.55, 'andil' => 0.0],
        //     ['kd_komoditas' => "016", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.13, 'andil' => 0.0],
        //     ['kd_komoditas' => "017", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 5.53, 'andil' => 0.0],
        //     ['kd_komoditas' => "018", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 0.59, 'andil' => 0.0],
        //     ['kd_komoditas' => "019", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -24.42, 'andil' => 0.0],
        //     ['kd_komoditas' => "020", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -12.31, 'andil' => 0.0],
        //     ['kd_komoditas' => "021", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 37.12, 'andil' => 0.0],
        //     ['kd_komoditas' => "022", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -0.07, 'andil' => 0.0],
        //     ['kd_komoditas' => "023", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 2.2, 'andil' => 0.0],
        //     ['kd_komoditas' => "024", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => 1.56, 'andil' => 0.0],
        //     ['kd_komoditas' => "027", 'kd_wilayah' => "16", 'bulan_tahun_id' => $id, 'kd_level' => "01", 'harga' => -4.64, 'andil' => 0.0],

        // ]);
//     }
// }
