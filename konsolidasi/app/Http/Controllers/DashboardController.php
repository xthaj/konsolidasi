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

            // Cache ALL rekonsiliasi rows joined with inflasi.kd_wilayah for the active month
            $rekonsiliasiData = Rekonsiliasi::with(['inflasi:inflasi_id,kd_wilayah'])
                ->where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)
                ->get();

            return [
                'activeBulanTahun' => $activeBulanTahun,
                'rekonsiliasiData' => $rekonsiliasiData
            ];
        });

        // Retrieve cached values
        $activeBulanTahun = $dashboardData['activeBulanTahun'];
        $rekonsiliasiData = $dashboardData['rekonsiliasiData'];
        $activeMonthYear = $activeBulanTahun->getBulanName($activeBulanTahun->bulan) . " {$activeBulanTahun->tahun}";

        // Default values
        $percentage = -1;
        $progressWidth = null;

        // Logging
        if (auth()->user()->isPusat()) {
            $total = $rekonsiliasiData->count();
            $filled = $rekonsiliasiData->whereNotNull('alasan')->where('alasan', '!=', '')->count();

            $percentage = $total > 0 ? round(($filled / $total) * 100) : -1;
            $progressWidth = $percentage > -1 ? max($percentage, 10) : null;

            Log::info('Dashboard access', [
                'user_type' => 'pusat',
                'percentage' => $percentage
            ]);
        } else {
            $kdWilayah = auth()->user()->kd_wilayah;
            $filtered = $rekonsiliasiData->filter(function ($row) use ($kdWilayah) {
                return $row->inflasi && $row->inflasi->kd_wilayah === $kdWilayah;
            });
            $total = $filtered->count();
            $filled = $filtered->whereNotNull('alasan')->where('alasan', '!=', '')->count();

            $percentage = $total > 0 ? round(($filled / $total) * 100) : -1;
            $progressWidth = $percentage > -1 ? max($percentage, 10) : null;

            Log::info('Dashboard access', [
                'user_type' => 'daerah',
                'kd_wilayah' => $kdWilayah,
                'percentage' => $percentage
            ]);
        }

        return view('dashboard', compact('activeMonthYear', 'percentage', 'progressWidth'));
    }
}
