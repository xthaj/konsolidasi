<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
            : "Tidak ditemukan"; // Fallback if no active period

        // Initialize percentage
        $percentage = -1; // Default: no reconciliations

        if ($activeBulanTahun) {
            // Total reconciliations for the active month
            $totalRekonsiliasi = Rekonsiliasi::where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)
                ->count();

            if ($totalRekonsiliasi > 0) {
                // Reconciliations with 'alasan' filled
                $filledRekonsiliasi = Rekonsiliasi::where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)
                    ->whereNotNull('alasan')
                    ->where('alasan', '!=', '')
                    ->count();

                // Calculate percentage
                $percentage = round(($filledRekonsiliasi / $totalRekonsiliasi) * 100);
            }
        }

        return view('dashboard', [
            'activeMonthYear' => $activeMonthYear,
            'percentage' => $percentage
        ]);
    }
}
