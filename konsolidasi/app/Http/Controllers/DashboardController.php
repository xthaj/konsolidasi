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

        // Format active month and year as "Februari 2025"
        $monthNames = [
            '1' => 'Januari',
            '2' => 'Februari',
            '3' => 'Maret',
            '4' => 'April',
            '5' => 'Mei',
            '6' => 'Juni',
            '7' => 'Juli',
            '8' => 'Agustus',
            '9' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];

        $activeMonthYear = $activeBulanTahun
            ? "{$monthNames[$activeBulanTahun->bulan]} {$activeBulanTahun->tahun}"
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
