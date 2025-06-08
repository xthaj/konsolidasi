<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Rekonsiliasi;


class RekonSeeder2 extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run()
    {
        DB::transaction(function () {
            // ... Existing user creation code (unchanged, creates 500 users) ...

            // REKON CREATION
            $activeBulanTahun = Cache::get('bt_aktif')['bt_aktif'] ?? throw new \Exception('No active BulanTahun');

            // Build inflasi query
            $inflasiQuery = DB::table('inflasi')
                ->where('kd_komoditas', '!=', '0')
                ->where('kd_wilayah', '!=', '0')
                ->where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)
                ->whereNotNull('nilai_inflasi')
                ->whereIn('kd_level', ['01', '02', '03', '04', '05']);

            // ADD // Log raw SQL
            Log::info('Inflasi query', [
                'sql' => $inflasiQuery->toSql(),
                'bindings' => $inflasiQuery->getBindings(),
                'timestamp' => now(),
            ]);

            // Get inflasi records
            $inflasiList = $inflasiQuery
                ->select('inflasi_id', 'kd_level')
                ->get()
                ->groupBy('kd_level')
                ->map(function ($group) {
                    return $group->pluck('inflasi_id');
                });

            $levels = array_keys($inflasiList->toArray());

            // Log available kd_level and inflasi counts
            Log::info('Available kd_level values', [
                'levels' => $levels,
                'timestamp' => now(),
            ]);
            foreach ($levels as $level) {
                Log::info('Available inflasi for kd_level', [
                    'kd_level' => $level,
                    'inflasi_count' => $inflasiList[$level]->count(),
                    'timestamp' => now(),
                ]);
            }

            $rekonId = 1001;
            foreach ($levels as $level) {
                $inflasiForLevel = $inflasiList[$level]->shuffle();
                $count = min(50, $inflasiForLevel->count());
                for ($i = 0; $i < $count; $i++) {
                    Rekonsiliasi::factory()->create([
                        'inflasi_id' => $inflasiForLevel[$i],
                        'bulan_tahun_id' => $activeBulanTahun->bulan_tahun_id,
                    ]);
                    $rekonId++;
                }
                Log::info('Rekonsiliasi created for kd_level', [
                    'kd_level' => $level,
                    'count' => $count,
                    'timestamp' => now(),
                ]);
            }
        });
    }
}
