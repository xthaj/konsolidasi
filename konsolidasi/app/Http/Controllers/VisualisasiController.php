<?php

namespace App\Http\Controllers;

use App\Models\BulanTahun;
use App\Models\Inflasi;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

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
            'kd_komoditas' => $request->input('kd_komoditas', '000'),
            'errors' => []
        ];

        Log::info('Request Inputs:', $response);

        $bulanTahunRecord = $this->resolveBulanTahun($response['bulan'], $response['tahun']);
        if (!$bulanTahunRecord) {
            $response['message'] = 'Bulan dan tahun tidak ditemukan.';
            Log::warning('BulanTahun not found for bulan: ' . $response['bulan'] . ', tahun: ' . $response['tahun']);
            return view('visualisasi.harmonisasi', $response);
        }

        $response['bulan'] = $bulanTahunRecord->bulan;
        $response['tahun'] = $bulanTahunRecord->tahun;
        $request->merge(['bulan' => $bulanTahunRecord->bulan, 'tahun' => $bulanTahunRecord->tahun]);
        Log::info('Resolved BulanTahun:', [
            'bulan' => $response['bulan'],
            'tahun' => $response['tahun'],
            'bulan_tahun_id' => $bulanTahunRecord->bulan_tahun_id
        ]);

        $dataCheck = $this->cekDataLogic(
            $bulanTahunRecord->bulan_tahun_id,
            $response['kd_wilayah'],
            $response['kd_komoditas']
        );
        Log::info('DataCheck Result:', $dataCheck);

        $monthNames = (new BulanTahun())->monthNames ?? $this->defaultMonthNames();
        $wilayahName = $response['kd_wilayah'] === '0'
            ? 'Nasional'
            : (Wilayah::where('kd_wilayah', $response['kd_wilayah'])->value('nama_wilayah') ?? 'Unknown');
        $monthName = $monthNames[sprintf('%02d', $response['bulan'])] ?? $response['bulan'];
        $response['title'] = trim("Inflasi {$wilayahName} {$monthName} {$response['tahun']}");
        $response['data'] = $dataCheck['data']; // Includes stackedLine, horizontalBar, summary, etc.

        if (!empty($dataCheck['errors'])) {
            $response['errors'] = $dataCheck['errors'];
            $response['message'] = 'Beberapa data tidak tersedia.';
            Log::warning('Errors found in dataCheck:', $dataCheck['errors']);
        } else {
            Log::info('No errors, data should be passed:', $response['data']);
        }

        return view('visualisasi.harmonisasi', $response);
    }

    private function resolveBulanTahun(?string $bulan, ?string $tahun): ?BulanTahun
    {
        if ($bulan && $tahun) {
            return BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->first();
        }
        return BulanTahun::where('aktif', 1)->first();
    }

    private function cekDataLogic($bulanTahunId, $kd_wilayah, $kd_komoditas): array
    {
        // Fetch the BulanTahun record to validate the input ID
        $bulanTahunRecord = BulanTahun::find($bulanTahunId);
        if (!$bulanTahunRecord) {
            return [
                'charts' => [],
                'chart_status' => [],
                'data' => [],
                'errors' => ['Bulan dan tahun tidak ditemukan.']
            ];
        }

        // Load month names and level names for human-readable output
        $monthNames = (new BulanTahun())->monthNames ?? $this->defaultMonthNames();
        $levelNames = $this->defaultLevelNames();

        // Get data for the previous 5 months to support time-series charts
        $monthsData = $this->getPreviousMonths($bulanTahunRecord->bulan, $bulanTahunRecord->tahun, 5);
        if (empty($monthsData['ids'])) {
            return [
                'charts' => [],
                'chart_status' => [],
                'data' => [],
                'errors' => ["Data untuk bulan {$monthNames[$bulanTahunRecord->bulan]} tahun {$bulanTahunRecord->tahun} tidak ditemukan."]
            ];
        }

        // Initialize arrays to store results
        $errors = [];
        $charts = [];
        $chart_status = [];
        $data = [];

        // Determine the scope based on kd_wilayah length
        $isNational = $kd_wilayah === '0';
        $isProvince = strlen($kd_wilayah) === 2 && !$isNational;
        $isKabkot = strlen($kd_wilayah) === 4;

        // --- Stacked Line and Horizontal Bar Charts (Apply to All Scopes) ---
        $stackedLineData = [
            'series' => [],
            'xAxis' => array_map(fn($m) => $monthNames[$m], $monthsData['bulans'])
        ];
        $horizontalBarData = [
            'labels' => array_map(fn($m) => $monthNames[$m], $monthsData['bulans']),
            'datasets' => [],
            'regions' => [$kd_wilayah]
        ];

        $summaryData = []; // New array for divs

        $wilayahName = $isNational ? 'Nasional' : (Wilayah::where('kd_wilayah', $kd_wilayah)->value('nama_wilayah') ?? 'Unknown');

        foreach ($levelNames as $kd => $name) {
            $inflasiSeriesData = [];
            $andilData = [];
            $hasCompleteData = true;

            foreach ($monthsData['ids'] as $index => $id) {
                $record = Inflasi::where('bulan_tahun_id', $id)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_level', $kd)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('inflasi', 'andil')
                    ->first();

                $dataExists = $record && !is_null($record->inflasi) && (!is_null($record->andil) || !$isNational);
                $inflasi = $dataExists ? $record->inflasi : 0;
                $andil = $dataExists && $isNational ? $record->andil : 0;

                $inflasiSeriesData[] = $inflasi;
                $andilData[] = $andil;

                if (!$dataExists) {
                    $monthName = $monthNames[$monthsData['bulans'][$index]];
                    $errors[] = "Data bulan {$monthName} level harga {$name} tidak tersedia.";
                    $hasCompleteData = false;
                }
            }

            $stackedLineData['series'][] = [
                'name' => $name,
                'data' => $inflasiSeriesData, // Inflasi values
                'andil' => $andilData        // Andil values
            ];
            $horizontalBarData['datasets'][] = [
                'label' => $name,
                'inflasi' => $inflasiSeriesData,
                'andil' => $andilData,
                'region' => $kd_wilayah,
                'region_name' => $wilayahName
            ];

            $summaryData[$name] = [
                'inflasi' => end($inflasiSeriesData), // Latest inflasi
                'andil' => end($andilData)           // Latest andil
            ];

            $charts[] = 'stackedLine';
            $chart_status['stackedLine'] = $hasCompleteData ? 'complete' : 'incomplete';
            $charts[] = 'horizontalBar';
            $chart_status['horizontalBar'] = $hasCompleteData ? 'complete' : 'incomplete';
        }

        $data['stackedLine'] = $stackedLineData;
        $data['horizontalBar'] = $horizontalBarData;
        $data['summary'] = $summaryData; // Add summary to data

        // --- National Scope (kd_wilayah = '0') ---
        if ($isNational) {
            $provinces = Wilayah::whereRaw('CHAR_LENGTH(kd_wilayah) = 2')
                ->pluck('nama_wilayah', 'kd_wilayah')
                ->toArray();
            $kabkots = Wilayah::whereRaw('CHAR_LENGTH(kd_wilayah) = 4')
                ->pluck('nama_wilayah', 'kd_wilayah')
                ->toArray();
            $kdLevels = array_keys($levelNames);
            $latestMonthId = $monthsData['ids'][0];

            $heatmapData = [
                'xAxis' => array_values($levelNames),
                'yAxis' => array_values($provinces),
                'values' => [],
                'regions' => array_keys($provinces)
            ];
            $barChartData = [];
            $stackedBarData = [
                'labels' => array_values($levelNames),
                'datasets' => [
                    ['label' => 'Menurun (<0)', 'stack' => 'inflation', 'data' => [], 'backgroundColor' => '#EE6666'],
                    ['label' => 'Stabil (=0)', 'stack' => 'inflation', 'data' => [], 'backgroundColor' => '#FFCE34'],
                    ['label' => 'Naik (>0)', 'stack' => 'inflation', 'data' => [], 'backgroundColor' => '#91CC75'],
                    ['label' => 'Data tidak tersedia', 'stack' => 'inflation', 'data' => [], 'backgroundColor' => '#DCDDE2'],
                ],
            ];
            $provHorizontalBarData = [];
            $kabkotHorizontalBarData = [];

            foreach ($kdLevels as $kdLevel) {
                $missingProvs = [];
                $missingKabkots = [];
                $provInflasiComplete = true;
                $kabkotInflasiComplete = true;

                // Check province data for all levels
                foreach ($provinces as $provKd => $provName) {
                    $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                        ->where('kd_wilayah', $provKd)
                        ->where('kd_level', $kdLevel)
                        ->where('kd_komoditas', $kd_komoditas)
                        ->select('inflasi')
                        ->first();

                    if (!$record || is_null($record->inflasi)) {
                        $missingProvs[] = $provName;
                        $provInflasiComplete = false;
                    }
                }

                // Check kabkot data only for kd_level = '01' (Harga Konsumen Kota)
                if ($kdLevel === '01') {
                    foreach ($kabkots as $kabKd => $kabName) {
                        $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                            ->where('kd_wilayah', $kabKd)
                            ->where('kd_level', $kdLevel)
                            ->where('kd_komoditas', $kd_komoditas)
                            ->select('inflasi')
                            ->first();

                        if (!$record || is_null($record->inflasi)) {
                            $missingKabkots[] = $kabName;
                            $kabkotInflasiComplete = false;
                        }
                    }
                }

                // Generate error message with conditional "data provinsi"
                if (!empty($missingProvs) || (!empty($missingKabkots) && $kdLevel === '01')) {
                    $msg = "Di level {$levelNames[$kdLevel]}, ";
                    if (!empty($missingProvs)) {
                        $msg .= count($missingProvs) < 15
                            ? "data provinsi " . implode(', ', $missingProvs)
                            : count($missingProvs) . " data provinsi";
                    }
                    if ($kdLevel === '01' && !empty($missingKabkots)) {
                        $msg .= (!empty($missingProvs) ? " dan " : "");
                        $msg .= count($missingKabkots) < 15
                            ? "data " . implode(', ', $missingKabkots)
                            : count($missingKabkots) . " data kabupaten/kota";
                    }
                    $msg .= " tidak tersedia.";
                    $errors[] = $msg;
                }

                // Populate heatmap, bar, and stacked bar data
                foreach ($provinces as $provKd => $provName) {
                    $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                        ->where('kd_wilayah', $provKd)
                        ->where('kd_level', $kdLevel)
                        ->where('kd_komoditas', $kd_komoditas)
                        ->select('inflasi')
                        ->first();
                    $xIndex = array_search($kdLevel, $kdLevels);
                    $yIndex = array_search($provName, $heatmapData['yAxis']);
                    $heatmapData['values'][] = [
                        $xIndex,
                        $yIndex,
                        $record && !is_null($record->inflasi) ? (float)$record->inflasi : null
                    ];
                }

                $barChartData[] = [
                    'name' => $levelNames[$kdLevel],
                    'provinces' => array_values($provinces),
                    'values' => array_map(fn($provKd) => Inflasi::where('bulan_tahun_id', $latestMonthId)
                        ->where('kd_wilayah', $provKd)
                        ->where('kd_level', $kdLevel)
                        ->where('kd_komoditas', $kd_komoditas)
                        ->value('inflasi') ?? 0, array_keys($provinces))
                ];

                $counts = ['menurun' => 0, 'stable' => 0, 'naik' => 0, 'na' => 0];
                foreach ($provinces as $provKd => $provName) {
                    $inflasi = Inflasi::where('bulan_tahun_id', $latestMonthId)
                        ->where('kd_wilayah', $provKd)
                        ->where('kd_level', $kdLevel)
                        ->where('kd_komoditas', $kd_komoditas)
                        ->value('inflasi');
                    if (is_null($inflasi)) $counts['na']++;
                    elseif ($inflasi < 0) $counts['menurun']++;
                    elseif ($inflasi == 0) $counts['stable']++;
                    else $counts['naik']++;
                }
                $stackedBarData['datasets'][0]['data'][] = $counts['menurun'];
                $stackedBarData['datasets'][1]['data'][] = $counts['stable'];
                $stackedBarData['datasets'][2]['data'][] = $counts['naik'];
                $stackedBarData['datasets'][3]['data'][] = $counts['na'];

                // Province Horizontal Bar Data (sorted)
                $provRegions = array_keys($provinces);
                $provNames = array_values($provinces);
                $provInflasi = array_map(
                    fn($provKd) => Inflasi::where('bulan_tahun_id', $latestMonthId)
                        ->where('kd_wilayah', $provKd)
                        ->where('kd_level', $kdLevel)
                        ->where('kd_komoditas', $kd_komoditas)
                        ->value('inflasi') ?? null,
                    $provRegions
                );
                // Sort by inflasi in descending order, handling null values
                array_multisort(
                    $provInflasi,
                    SORT_ASC,
                    SORT_NUMERIC,
                    array_map(fn($val) => $val === null ? PHP_INT_MAX : 0, $provInflasi),
                    SORT_ASC, // Push nulls to the end
                    $provRegions,
                    $provNames
                );

                $provHorizontalBarData[] = [
                    'kd_level' => $kdLevel,
                    'regions' => $provRegions,
                    'names' => $provNames,
                    'inflasi' => $provInflasi
                ];

                // Only include kabkot data for kd_level = '01'
                if ($kdLevel === '01') {
                    $kabkotRegions = array_keys($kabkots);
                    $kabkotNames = array_values($kabkots);
                    $kabkotInflasi = array_map(
                        fn($kabKd) => Inflasi::where('bulan_tahun_id', $latestMonthId)
                            ->where('kd_wilayah', $kabKd)
                            ->where('kd_level', $kdLevel)
                            ->where('kd_komoditas', $kd_komoditas)
                            ->value('inflasi') ?? null,
                        $kabkotRegions
                    );

                    // Sort by inflasi in descending order, handling null values
                    array_multisort(
                        $kabkotInflasi,
                        SORT_ASC,
                        SORT_NUMERIC,
                        array_map(fn($val) => $val === null ? PHP_INT_MAX : 0, $kabkotInflasi),
                        SORT_ASC, // Push nulls to the end
                        $kabkotRegions,
                        $kabkotNames
                    );

                    $kabkotHorizontalBarData[] = [
                        'kd_level' => $kdLevel,
                        'regions' => $kabkotRegions,
                        'names' => $kabkotNames,
                        'inflasi' => $kabkotInflasi
                    ];
                }
            }

            $charts = array_merge($charts, ['heatmap', 'barCharts', 'stackedBar', 'provHorizontalBar']);
            if (!empty($kabkotHorizontalBarData)) {
                $charts[] = 'kabkotHorizontalBar';
            }
            $chart_status = array_merge($chart_status, [
                'heatmap' => $provInflasiComplete ? 'complete' : 'incomplete',
                'barCharts' => $provInflasiComplete ? 'complete' : 'incomplete',
                'stackedBar' => $provInflasiComplete ? 'complete' : 'incomplete',
                'provHorizontalBar' => $provInflasiComplete ? 'complete' : 'incomplete',
                'kabkotHorizontalBar' => $kabkotInflasiComplete && !empty($kabkotHorizontalBarData) ? 'complete' : 'incomplete'
            ]);
            $data['heatmap'] = $heatmapData;
            $data['barCharts'] = $barChartData;
            $data['stackedBar'] = $stackedBarData;
            $data['provHorizontalBar'] = $provHorizontalBarData;
            if (!empty($kabkotHorizontalBarData)) {
                $data['kabkotHorizontalBar'] = $kabkotHorizontalBarData;
            }
        }

        // --- Province Scope (kd_wilayah is 2 digits) ---
        if ($isProvince) {
            $kabkots = Wilayah::where('parent_kd', $kd_wilayah)->pluck('nama_wilayah', 'kd_wilayah')->toArray();
            $latestMonthId = $monthsData['ids'][0];

            foreach ($levelNames as $kd => $name) {
                // Only check kabkots for kd_level = '01'
                if ($kd === '01') {
                    $missingKabkots = [];
                    foreach ($kabkots as $kabKd => $kabName) {
                        $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                            ->where('kd_wilayah', $kabKd)
                            ->where('kd_level', $kd)
                            ->where('kd_komoditas', $kd_komoditas)
                            ->select('inflasi')
                            ->first();

                        if (!$record || is_null($record->inflasi)) {
                            $missingKabkots[] = $kabName;
                        }
                    }

                    if (!empty($missingKabkots)) {
                        $errors[] = "Di level {$name}, data " . implode(', ', $missingKabkots) . " tidak tersedia.";
                    }
                }
            }

            $chart_status['heatmap'] = 'not_applicable';
            $chart_status['barCharts'] = 'not_applicable';
            $chart_status['provHorizontalBar'] = 'not_applicable';
            $chart_status['kabkotHorizontalBar'] = 'not_applicable';
        }

        // --- Kabupaten/Kota Scope (kd_wilayah is 4 digits) ---
        if ($isKabkot) {
            $chart_status['heatmap'] = 'not_applicable';
            $chart_status['barCharts'] = 'not_applicable';
            $chart_status['provHorizontalBar'] = 'not_applicable';
            $chart_status['kabkotHorizontalBar'] = 'not_applicable';
        }

        return [
            'charts' => array_unique($charts),
            'chart_status' => $chart_status,
            'data' => $data,
            'errors' => $errors
        ];
    }
    private function getPreviousMonths(string $bulan, string $tahun, int $count): array
    {
        $ids = [];
        $bulans = [];
        $tahuns = [];
        $currentBulan = (int) $bulan;
        $currentTahun = (int) $tahun;

        for ($i = 0; $i < $count; $i++) {
            Log::info("Processing: currentBulan={$currentBulan}, currentTahun={$currentTahun}");
            $bt = BulanTahun::where('bulan', sprintf('%02d', $currentBulan))
                ->where('tahun', $currentTahun)
                ->first();

            $ids[] = $bt ? $bt->bulan_tahun_id : null;
            $bulans[] = sprintf('%02d', $currentBulan);
            $tahuns[] = $currentTahun;

            // Move to previous month
            $currentBulan = ($currentBulan == 1) ? 12 : $currentBulan - 1;
            if ($currentBulan == 12) {
                $currentTahun--;
            }
        }

        return [
            'ids' => array_reverse($ids),
            'bulans' => array_reverse($bulans),
            'tahuns' => array_reverse($tahuns),
        ];
    }

    private function generatePageTitle(Request $request): string
    {
        $monthNames = (new BulanTahun())->monthNames ?? $this->defaultMonthNames();

        // Use resolved bulan and tahun from the request, falling back to defaults if not set
        $bulan = $request->input('bulan', '');
        $tahun = $request->input('tahun', '');
        $kd_wilayah = $request->input('kd_wilayah', '0');

        // Fetch wilayah name or default to 'Nasional'
        $wilayah = $kd_wilayah === '0'
            ? 'Nasional'
            : (Wilayah::where('kd_wilayah', $kd_wilayah)->value('nama_wilayah') ?? 'Unknown');


        // Ensure bulan is padded and valid, fallback to empty if invalid
        $monthKey = sprintf('%02d', (int)$bulan);
        $monthName = $monthNames[$monthKey] ?? '';

        // Construct title, trimming extra spaces
        return trim("Inflasi {$wilayah} {$monthName} {$tahun}");
    }

    private function defaultMonthNames(): array
    {
        return [
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
    }

    private function defaultLevelNames(): array
    {
        return [
            '01' => 'Harga Konsumen Kota',
            '02' => 'Harga Konsumen Desa',
            '03' => 'Harga Perdagangan Besar',
            '04' => 'Harga Produsen Desa',
            '05' => 'Harga Produsen'
        ];
    }
}
