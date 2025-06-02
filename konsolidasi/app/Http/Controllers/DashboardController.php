<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\BulanTahun;
use App\Models\Rekonsiliasi;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activeBulanTahun = BulanTahun::where('aktif', 1)->firstOrFail();

        $activeMonthYear = $activeBulanTahun->getBulanName($activeBulanTahun->bulan) . " {$activeBulanTahun->tahun}";

        $percentage = -1;
        $progressWidth = null; // For minimum visual width of the bar

        $totalRekonsiliasi = Rekonsiliasi::where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)->count();

        if ($totalRekonsiliasi > 0) {
            $filledRekonsiliasi = Rekonsiliasi::where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)
                ->whereNotNull('alasan')
                ->where('alasan', '!=', '')
                ->count();

            $percentage = round(($filledRekonsiliasi / $totalRekonsiliasi) * 100);
            $progressWidth = max($percentage, 5); // Ensures at least 5% width
        }

        // Added: Minimal logging for debugging
        Log::info('Dashboard data loaded', [
            'activeMonthYear' => $activeMonthYear,
            'percentage' => $percentage,
        ]);

        return view('dashboard', [
            'activeMonthYear' => $activeMonthYear,
            'percentage' => $percentage,
            'progressWidth' => $progressWidth,
        ]);
    }
}
