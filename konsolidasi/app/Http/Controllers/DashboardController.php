<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\BulanTahun;
use App\Models\Rekonsiliasi;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Get active BulanTahun
        $activeBulanTahun = BulanTahun::where('aktif', 1)->first();

        $activeMonthYear = $activeBulanTahun
            ? $activeBulanTahun->getBulanName($activeBulanTahun->bulan) . " {$activeBulanTahun->tahun}"
            : "Tidak ditemukan";

        $percentage = -1; // Default: no reconciliations
        $progressWidth = null; // For minimum visual width of the bar

        if ($activeBulanTahun) {
            $totalRekonsiliasi = Rekonsiliasi::where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)->count();

            if ($totalRekonsiliasi > 0) {
                $filledRekonsiliasi = Rekonsiliasi::where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)
                    ->whereNotNull('alasan')
                    ->where('alasan', '!=', '')
                    ->count();

                $percentage = round(($filledRekonsiliasi / $totalRekonsiliasi) * 100);
                $progressWidth = max($percentage, 5); // Ensures at least 10% width
            }
        }

        return view('dashboard', [
            'activeMonthYear' => $activeMonthYear,
            'percentage' => $percentage,
            'progressWidth' => $progressWidth,
        ]);
    }
}
