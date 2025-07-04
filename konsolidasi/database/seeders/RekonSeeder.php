<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wilayah;
use App\Models\BulanTahun;
use App\Models\Inflasi;
use App\Models\User;
use App\Models\Rekonsiliasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RekonSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            // Get and shuffle all non-0 wilayah
            $wilayahList = Wilayah::where('kd_wilayah', '!=', '0')->pluck('kd_wilayah')->shuffle();
            if ($wilayahList->isEmpty()) {
                throw new \Exception('No valid kd_wilayah found in wilayah table.');
            }

            // Create 100 pusat users
            for ($i = 1; $i <= 100; $i++) {
                User::factory()->create([
                    'username' => "pusat_user_{$i}",
                    'level' => $i % 2 === 0 ? 0 : 1,
                    'kd_wilayah' => '0',
                ]);
            }

            // Create 400 non-pusat users, alternating between provinsi and kabkot
            $userId = 101;
            $provinsiCount = 0;
            $kabkotCount = 0;

            while ($userId <= 500) {
                foreach ($wilayahList as $kdWilayah) {
                    if ($userId > 500) break;

                    $isProvinsi = strlen($kdWilayah) === 2;
                    if ($isProvinsi && $provinsiCount >= 100) continue;

                    $level = match (true) {
                        $isProvinsi && $userId % 2 === 0 => 2,
                        $isProvinsi => 3,
                        $userId % 2 === 0 => 4,
                        default => 5,
                    };

                    User::factory()->create([
                        'username' => "user_{$userId}",
                        'level' => $level,
                        'kd_wilayah' => $kdWilayah,
                    ]);

                    $userId++;
                    $isProvinsi ? $provinsiCount++ : $kabkotCount++;
                }
            }

            // Seed Rekonsiliasi
            $activeBulanTahun = Cache::get('bt_aktif')['bt_aktif'] ?? throw new \Exception('No active BulanTahun');
            $bt_id = $activeBulanTahun->bulan_tahun_id;

            $inflasiGrouped = DB::table('inflasi')
                ->where('kd_komoditas', '!=', '0')
                ->where('kd_wilayah', '!=', '0')
                ->where('bulan_tahun_id', $bt_id)
                ->whereNotNull('nilai_inflasi')
                ->get()
                ->groupBy('kd_level');

            Log::info('Retrieved inflasi records', [
                'total_inflasi' => $inflasiGrouped->flatten(1)->count(),
                'levels_found' => $inflasiGrouped->keys(),
                'timestamp' => now()
            ]);

            $totalRekonCreated = 0;
            foreach ($inflasiGrouped as $level => $records) {
                $inflasiIds = $records->pluck('inflasi_id')->shuffle()->take(50);

                foreach ($inflasiIds as $inflasi_id) {
                    Rekonsiliasi::factory()->create([
                        'inflasi_id' => $inflasi_id,
                        'bulan_tahun_id' => $bt_id,
                    ]);
                }

                $count = $inflasiIds->count();
                $totalRekonCreated += $count;

                Log::info('Rekonsiliasi created for kd_level', [
                    'kd_level' => $level,
                    'count' => $count,
                    'timestamp' => now(),
                ]);
            }

            Log::info('Total Rekonsiliasi created', [
                'count' => $totalRekonCreated,
                'timestamp' => now(),
            ]);

            Log::info('Completed RekonSeeder process', ['timestamp' => now()]);
        });
    }
}