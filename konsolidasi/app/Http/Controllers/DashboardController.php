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
            $activeMonthYear = $activeBulanTahun->getBulanName($activeBulanTahun->bulan) . " {$activeBulanTahun->tahun}";

            $percentage = -1;
            $progressWidth = null;

            $totalRekonsiliasi = Rekonsiliasi::where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)->count();

            if ($totalRekonsiliasi > 0) {
                $filledRekonsiliasi = Rekonsiliasi::where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)
                    ->whereNotNull('alasan')
                    ->where('alasan', '!=', '')
                    ->count();

                $percentage = round(($filledRekonsiliasi / $totalRekonsiliasi) * 100);
                $progressWidth = max($percentage, 10);
            }

            return compact('activeMonthYear', 'percentage', 'progressWidth');
        });

        return view('dashboard', $dashboardData);
    }
}
