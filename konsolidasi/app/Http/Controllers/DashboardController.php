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
        $viewData = [
            'activeMonthYear' => 'Tidak ditemukan',
            'percentage' => 0,
            'progressWidth' => 0,
            'message' => 'Silakan aktifkan periode untuk melihat progres.',
            'error' => null,
        ];

        try {
            $activeBulanTahun = BulanTahun::where('aktif', 1)->first();

            if (!$activeBulanTahun) {
                // edit here: Treat as error state
                Log::warning('No active BulanTahun found for dashboard');
                $viewData['error'] = 'Tidak ada periode aktif ditemukan.';
                return view('dashboard', $viewData);
            }

            $activeMonthYear = $activeBulanTahun->getBulanName($activeBulanTahun->bulan) . " {$activeBulanTahun->tahun}";

            $rekonsiliasiStats = Rekonsiliasi::where('bulan_tahun_id', $activeBulanTahun->bulan_tahun_id)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN alasan IS NOT NULL AND alasan != "" THEN 1 ELSE 0 END) as filled')
                ->first();

            $totalRekonsiliasi = $rekonsiliasiStats->total ?? 0;
            $filledRekonsiliasi = $rekonsiliasiStats->filled ?? 0;

            if ($totalRekonsiliasi > 0) {
                $percentage = round(($filledRekonsiliasi / $totalRekonsiliasi) * 100);
                $progressWidth = max($percentage, 5); // Minimum 5% for visibility
                $message = "$filledRekonsiliasi dari $totalRekonsiliasi rekonsiliasi telah diisi.";
            } else {
                // edit here: Clearer empty state
                $percentage = 0;
                $progressWidth = 0;
                $message = 'Tidak ada data rekonsiliasi untuk periode ini.';
            }

            // add here: Log success
            Log::info('Dashboard data loaded', [
                'activeMonthYear' => $activeMonthYear,
                'percentage' => $percentage,
                'totalRekonsiliasi' => $totalRekonsiliasi,
            ]);

            $viewData = [
                'activeMonthYear' => $activeMonthYear,
                'percentage' => $percentage,
                'progressWidth' => $progressWidth,
                'message' => $message,
                'error' => null,
            ];

            return view('dashboard', $viewData);
        } catch (\Exception $e) {
            // add here: Handle errors
            Log::error('Error loading dashboard', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            $viewData['error'] = 'Gagal memuat dashboard: ' . $e->getMessage();
            return view('dashboard', $viewData);
        }
    }
}
