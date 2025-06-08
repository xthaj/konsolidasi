<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Wilayah;
use App\Models\BulanTahun;
use App\Models\Inflasi;
use App\Models\User;
use App\Models\Rekonsiliasi;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Cache;



class RekonSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            // Get existing kd_wilayah from wilayah table
            $wilayahList = Wilayah::where('kd_wilayah', '!=', '0')
                ->pluck('kd_wilayah')
                ->shuffle();

            if ($wilayahList->isEmpty()) {
                throw new \Exception('No valid kd_wilayah found in wilayah table.');
            }

            // Create 100 Pusat users
            for ($i = 1; $i <= 100; $i++) {
                User::factory()->create([
                    'username' => "pusat_user_{$i}",
                    'level' => ($i % 2 == 0) ? 0 : 1, // Alternate Admin/Operator
                    'kd_wilayah' => '0',
                ]);
            }

            // Create 400 non-Pusat users by cycling through wilayahList
            $nonPusatTarget = 400;
            $provinsiCount = 0;
            $kabkotCount = 0;
            $userId = 101;

            while ($userId <= 500) { // Stop at 500 total users
                foreach ($wilayahList as $kdWilayah) {
                    if ($userId > 500) break; // Ensure exactly 400 non-Pusat
                    $isProvinsi = strlen($kdWilayah) == 2;
                    if ($isProvinsi && $provinsiCount >= 100) continue;

                    $level = $isProvinsi
                        ? ($userId % 2 == 0 ? 2 : 3) // Provinsi Admin/Operator
                        : ($userId % 2 == 0 ? 4 : 5); // Kabkot Admin/Operator

                    User::factory()->create([
                        'username' => "user_{$userId}",
                        'level' => $level,
                        'kd_wilayah' => $kdWilayah,
                    ]);

                    $userId++;
                    if ($isProvinsi) $provinsiCount++;
                    else $kabkotCount++;
                }
            }

            // REKON CREATION
            $activeBulanTahun = Cache::get('bt_aktif')['bt_aktif'] ?? throw new \Exception('No active BulanTahun');
            $bt_id = $activeBulanTahun->bulan_tahun_id;
            // Get inflasi records for active BulanTahun with non-null nilai_inflasi
            $inflasiList = DB::table('inflasi')
                ->where('kd_komoditas', '!=', '0')
                ->where('kd_wilayah', '!=', '0')
                ->where('bulan_tahun_id', $bt_id)
                ->whereNotNull('nilai_inflasi')
                ->pluck('inflasi_id', 'kd_level')
                ->groupBy('kd_level');

            $levels = array_keys($inflasiList->toArray()); // ['00', '01', '02', '03', '04']
            $rekonId = 1001; // Start from 1001 to avoid conflicts
            foreach ($levels as $level) {
                $inflasiForLevel = $inflasiList[$level]->shuffle();
                $count = min(50, $inflasiForLevel->count()); // 50 per level or less
                for ($i = 0; $i < $count; $i++) {
                    Rekonsiliasi::factory()->create([
                        'inflasi_id' => $inflasiForLevel[$i],
                        'bulan_tahun_id' => $activeBulanTahun->bulan_tahun_id,
                    ]);
                    $rekonId++;
                }
                // ADD // Log the number of Rekonsiliasi created for this kd_level
                Log::info('Rekonsiliasi created for kd_level', [
                    'kd_level' => $level,
                    'count' => $count,
                    'timestamp' => now(),
                ]);
            }
        });
    }
}
