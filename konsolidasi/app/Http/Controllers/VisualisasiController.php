<?php

namespace App\Http\Controllers;

use App\Models\BulanTahun;
use App\Models\Inflasi;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisualisasiController extends Controller
{
    public function create(Request $request): View
    {
        $response = [
            'title' => 'Inflasi',
            'message' => '',
            'bulan' => $request->input('bulan', ''),
            'tahun' => $request->input('tahun', ''),
            'kd_wilayah' => $request->input('kd_wilayah', '0'),
            'kd_komoditas' => $request->input('kd_komoditas', '00'),
        ];

        // Determine bulan_tahun_id
        $bulan = $response['bulan'];
        $tahun = $response['tahun'];
        if ($bulan && $tahun) {
            $bulanTahun = BulanTahun::where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->first();
        } else {
            $bulanTahun = BulanTahun::where('aktif', 1)->first();
            if ($bulanTahun) {
                $response['bulan'] = $bulanTahun->bulan;
                $response['tahun'] = $bulanTahun->tahun;
            }
        }

        $kd_wilayah = $response['kd_wilayah'];
        $kd_komoditas = $response['kd_komoditas'];

        // Check data availability
        $bulanTahunId = $bulanTahun ? $bulanTahun->bulan_tahun_id : null;
        $dataCheck = $this->cekDataLogic($bulanTahunId, $kd_wilayah, $kd_komoditas);

        // If no bulanTahun, return with error
        if (!$bulanTahun) {
            $response['message'] = $dataCheck['errors'][0];
            return view('visualisasi.harmonisasi', $response);
        }

        // Set title and update request
        $request->merge(['bulan' => $bulanTahun->bulan, 'tahun' => $bulanTahun->tahun]);
        $response['title'] = $this->generatePageTitle($request);

        // Prepare data for available charts
        $response['data'] = [];
        if (in_array('stackedLine', $dataCheck['charts'])) {
            $response['data']['stackedLine'] = $this->buildStackedLine($bulanTahunId, $kd_wilayah, $kd_komoditas);
        }
        if (in_array('barChart', $dataCheck['charts'])) {
            // $response['data']['barChart'] = $this->buildBarChart($bulanTahunId, $kd_wilayah, $kd_komoditas); // Add this method
        }
        if (in_array('futureChart', $dataCheck['charts'])) {
            // $response['data']['barChart'] = $this->buildBarChart($bulanTahunId, $kd_wilayah, $kd_komoditas); // Add this method
        }

        // Include errors in response if any
        if (!empty($dataCheck['errors'])) {
            $response['message'] = 'Beberapa data hilang: ' . implode(', ', $dataCheck['errors']);
        }

        return view('visualisasi.harmonisasi', $response);
    }

    private function cekDataLogic($bulanTahunId, $kd_wilayah, $kd_komoditas)
    {
        // Get the current BulanTahun
        $bulanTahun = BulanTahun::find($bulanTahunId);
        if (!$bulanTahun) {
            return [
                'charts' => [],
                'errors' => ['Bulan dan tahun tidak ditemukan.'],
                'bulanTahun' => null,
            ];
        }

        // Month names
        $monthNames = (new BulanTahun())->monthNames ?? [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];

        // Level names
        $levelNames = [
            '01' => 'Harga Konsumen Kota',
            '02' => 'Harga Konsumen Desa',
            '03' => 'Harga Perdagangan Besar',
            '04' => 'Harga Produsen Desa',
            '05' => 'Harga Produsen'
        ];

        // Get 5 months (current + 4 prior)
        $monthsToCheck = [];
        $currentBulan = (int) $bulanTahun->bulan;
        $currentTahun = (int) $bulanTahun->tahun;

        for ($i = 0; $i < 5; $i++) {
            $bt = BulanTahun::where('bulan', sprintf('%02d', $currentBulan))
                ->where('tahun', $currentTahun)
                ->first();

            if (!$bt) {
                return [
                    'charts' => [],
                    'errors' => ["Data untuk bulan $currentBulan tahun $currentTahun tidak ditemukan."],
                    'bulanTahun' => $bulanTahun,
                ];
            }
            $monthsToCheck[] = $bt;

            // Move to previous month
            if ($currentBulan != 1) {
                $currentBulan--;
            } else {
                $currentBulan = 12;
                $currentTahun--;
            }
        }

        // Data completeness check
        $errors = [];
        $stackedLineOk = true;
        $barChartOk = true;

        foreach ($monthsToCheck as $bt) {
            $monthName = $monthNames[$bt->bulan] ?? "Bulan {$bt->bulan}";
            foreach (['01', '02', '03', '04', '05'] as $level) {
                // Base check: Does any data exist for this level in this month?
                $levelExists = Inflasi::where('bulan_tahun_id', $bt->bulan_tahun_id)
                    ->where('kd_level', $level)
                    ->exists();

                if (!$levelExists) {
                    $errors[] = "Bulan {$monthName} tahun {$bt->tahun}, Level {$levelNames[$level]}: Tidak ada data.";
                    $stackedLineOk = false;
                    $barChartOk = false;

                    continue;
                }

                // Check for current charts (stackedLine and barChart) at specified kd_wilayah
                $query = Inflasi::where('bulan_tahun_id', $bt->bulan_tahun_id)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_level', $level)
                    ->where('kd_komoditas', $kd_komoditas);

                $dataExists = $query->whereNotNull('inflasi')
                    ->whereNotNull('andil')
                    ->exists();

                if (!$dataExists) {
                    $missingFields = [];
                    if (!$query->whereNotNull('inflasi')->exists()) {
                        $missingFields[] = 'inflasi';
                    }
                    if (!$query->whereNotNull('andil')->exists()) {
                        $missingFields[] = 'andil';
                    }
                    $errors[] = "Bulan {$monthName} tahun {$bt->tahun}, Level {$levelNames[$level]}: " . implode(' dan ', $missingFields) . " hilang.";
                    $stackedLineOk = false;
                    $barChartOk = false;
                }

                // Optional province-level check for future charts
                if ($kd_wilayah === '0') {
                    $provinces = Wilayah::where('kd_wilayah', 'like', '__')->get();
                    foreach ($provinces as $prov) {
                        $futureDataExists = Inflasi::where('bulan_tahun_id', $bt->bulan_tahun_id)
                            ->where('kd_wilayah', $prov->kd_wilayah)
                            ->where('kd_level', $level)
                            ->whereNotNull('inflasi')
                            ->whereNotNull('andil')
                            ->exists();
                        if (!$futureDataExists) {
                            $errors[] = "Bulan {$monthName} tahun {$bt->tahun}, Provinsi {$prov->nama_wilayah}, Level {$levelNames[$level]}: Inflasi atau andil hilang.";
                            $futureChartsOk = false;
                        }
                    }
                }
            }
        }

        // Determine which charts can be shown
        $charts = [];
        if ($stackedLineOk) {
            $charts[] = 'stackedLine';
        }
        if ($barChartOk) {
            $charts[] = 'barChart';
        }
        if ($futureChartsOk) {
            $charts[] = 'futureCharts'; // Placeholder for future use
        }

        return [
            'charts' => $charts,
            'errors' => $errors,
            'bulanTahun' => $bulanTahun,
        ];
    }

    private function prepareVisualizationData($bulanTahun, Request $request)
    {
        $kd_wilayah = $request->kd_wilayah;
        $kd_komoditas = $request->kd_komoditas;
        $bulanTahunId = $bulanTahun->bulan_tahun_id;

        return ['stackedLine' => $this->buildStackedLine($bulanTahunId, $kd_wilayah, $kd_komoditas)];
    }

    private function buildStackedLine($bulanTahunId, $kd_wilayah, $kd_komoditas)
    {
        $levels = [
            '01' => 'Harga Konsumen Kota',
            '02' => 'Harga Konsumen Desa',
            '03' => 'Harga Perdagangan Besar',
            '04' => 'Harga Produsen Desa',
            '05' => 'Harga Produsen'
        ];

        // Month names for display
        $monthNames = (new BulanTahun())->monthNames;

        // Get the current BulanTahun
        $bulanTahun = BulanTahun::find($bulanTahunId);
        if (!$bulanTahun) {
            return ['series' => [], 'xAxis' => []];
        }

        // Get 5 months (current + 4 prior)
        $months = [];
        $monthIds = [];
        $currentBulan = (int) $bulanTahun->bulan;
        $currentTahun = (int) $bulanTahun->tahun;

        for ($i = 0; $i < 5; $i++) {
            $bt = BulanTahun::where('bulan', sprintf('%02d', $currentBulan))
                ->where('tahun', $currentTahun)
                ->first();

            if (!$bt) {
                $months[] = "Bulan $currentBulan Tahun $currentTahun (Tidak Ada)";
                $monthIds[] = null;
            } else {
                $months[] = $monthNames[$bt->bulan];
                $monthIds[] = $bt->bulan_tahun_id;
            }

            // Move to the previous month
            if ($currentBulan != 1) {
                $currentBulan--;
            } else {
                $currentBulan = 12;
                $currentTahun--;
            }
        }

        // Reverse to show oldest to newest
        $months = array_reverse($months);
        $monthIds = array_reverse($monthIds);

        // Build chart data
        $data = ['series' => [], 'xAxis' => $months];
        foreach ($levels as $kd => $name) {
            $seriesData = [];
            foreach ($monthIds as $id) {
                $inflation = $id ? Inflasi::where('bulan_tahun_id', $id)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_level', $kd)
                    ->where('kd_komoditas', $kd_komoditas ?: DB::raw('kd_komoditas'))
                    ->value('inflasi') ?? 0 : 0;
                $seriesData[] = $inflation;
            }
            $data['series'][] = ['name' => $name, 'data' => $seriesData];
        }

        return $data;
    }

    private function generatePageTitle(Request $request): string
    {
        $monthNames = (new BulanTahun())->monthNames;
        $levelHargaMap = [
            '01' => 'Harga Konsumen Kota',
            '02' => 'Harga Konsumen Desa',
            '03' => 'Harga Perdagangan Besar',
            '04' => 'Harga Produsen Desa',
            '05' => 'Harga Produsen',
            'all' => 'Semua Level Harga'
        ];

        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $kd_level = $request->input('kd_level', 'all');
        $kd_wilayah = $request->kd_wilayah;

        $wilayah = $kd_wilayah === '0' ? 'Nasional' : (Wilayah::find($kd_wilayah)->nama_wilayah ?? 'Unknown');
        $levelHarga = $levelHargaMap[$kd_level] ?? '';
        $monthName = $monthNames[$bulan] ?? '';

        return trim("Inflasi {$wilayah} {$levelHarga} {$monthName} {$tahun}");
    }
}
