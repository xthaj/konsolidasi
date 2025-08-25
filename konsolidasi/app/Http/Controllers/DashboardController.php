<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\BulanTahun;
use App\Models\Rekonsiliasi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(): View
    {
        $cacheKey = 'dashboard_data';
        $dashboardData = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $activeBulanTahun = BulanTahun::where('aktif', 1)->firstOrFail();

            // Load inflasi.kd_wilayah and its wilayah.parent_kd
            $rekonsiliasiData = Rekonsiliasi::with([
                'inflasi' => function ($q) {
                    $q->select('inflasi_id', 'kd_wilayah')
                        ->with(['wilayah:kd_wilayah,parent_kd']);
                }
            ])
                ->where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)
                ->get();

            return [
                'activeBulanTahun' => $activeBulanTahun,
                'rekonsiliasiData' => $rekonsiliasiData
            ];
        });

        $activeBulanTahun = $dashboardData['activeBulanTahun'];
        $rekonsiliasiData = $dashboardData['rekonsiliasiData'];
        $activeMonthYear = $activeBulanTahun->getBulanName($activeBulanTahun->bulan) . " {$activeBulanTahun->tahun}";

        $percentage = -1;
        $progressWidth = null;

        // pusat: everything
        if (auth()->user()->isPusat()) {
            $total = $rekonsiliasiData->count();
            $filled = $rekonsiliasiData->whereNotNull('alasan')->where('alasan', '!=', '')->count();
        }
        // provinsi: kd_wilayah OR parent_kd
        elseif (auth()->user()->isProvinsi()) {
            $kdWilayah = auth()->user()->kd_wilayah;
            $filtered = $rekonsiliasiData->filter(function ($row) use ($kdWilayah) {
                $inflasi = $row->inflasi;
                if (!$inflasi) return false;
                $wilayah = $inflasi->wilayah;
                return $inflasi->kd_wilayah === $kdWilayah ||
                    ($wilayah && $wilayah->parent_kd === $kdWilayah);
            });
            $total = $filtered->count();
            $filled = $filtered->whereNotNull('alasan')->where('alasan', '!=', '')->count();
        }
        // daerah: only own kd_wilayah
        else {
            $kdWilayah = auth()->user()->kd_wilayah;
            $filtered = $rekonsiliasiData->filter(function ($row) use ($kdWilayah) {
                return $row->inflasi && $row->inflasi->kd_wilayah === $kdWilayah;
            });
            $total = $filtered->count();
            $filled = $filtered->whereNotNull('alasan')->where('alasan', '!=', '')->count();
        }

        if ($total > 0) {
            $percentage = round(($filled / $total) * 100);
            $progressWidth = max($percentage, 10);
        }

        // Log::info('Dashboard access', [
        //     'user_type' => auth()->user()->role ?? 'unknown',
        //     'kd_wilayah' => auth()->user()->kd_wilayah ?? null,
        //     'percentage' => $percentage,
        //     'total' => $total
        // ]);

        return view('dashboard', compact('activeMonthYear', 'percentage', 'progressWidth'));
    }
}
