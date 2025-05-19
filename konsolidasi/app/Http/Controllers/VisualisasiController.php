<?php

namespace App\Http\Controllers;

use App\Models\BulanTahun;
use App\Models\Inflasi;
use App\Models\Wilayah;
use App\Models\Komoditas;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class VisualisasiController extends Controller
{
    /**
     * Render the visualization view.
     *
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        Log::info('VisualisasiController@create called', [
            'request' => $request->all(),
            'headers' => $request->headers->all(),
            'session_id' => $request->session()->getId()
        ]);
        $response = $this->fetchVisualisasiData($request);
        return view('visualisasi.harmonisasi', $response);
    }

    /**
     * API endpoint to fetch visualization data for ECharts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function apiVisualisasi(Request $request): JsonResponse
    {
        Log::info('VisualisasiController@apiVisualisasi called', ['request' => $request->all()]);
        return $this->fetchVisualisasiData($request, true);
    }

    /**
     * Fetch visualization data for charts.
     *
     * @param Request $request
     * @param bool $isApi
     * @return array|JsonResponse
     */
    private function fetchVisualisasiData(Request $request, bool $isApi = false)
    {
        Log::info('VisualisasiController@fetchVisualisasiData called', ['request' => $request->all()]);

        $activeBulanTahun = BulanTahun::where('aktif', 1)->first();
        if (!$activeBulanTahun) {
            $response = [
                'title' => 'Inflasi',
                'errors' => ['Tidak ada periode aktif.'],
                'chart_status' => [],
                'chart_data' => []
            ];
            Log::warning('No active period found');
            return $this->formatResponse($response, $isApi, 'error', 400);
        }

        $defaults = [
            'bulan' => $activeBulanTahun->bulan,
            'tahun' => $activeBulanTahun->tahun,
            'level_wilayah' => 1,
            'kd_wilayah' => '0',
            'kd_komoditas' => '001'
        ];

        $input = array_merge($defaults, $request->only(array_keys($defaults)));

        try {
            $validated = $request->validate([
                'bulan' => 'required|integer|between:1,12',
                'tahun' => 'required|integer|between:2000,2100',
                'level_wilayah' => 'required|in:1,2',
                'kd_komoditas' => 'nullable|string|max:10',
                'kd_wilayah' => [
                    'nullable',
                    function ($attribute, $value, $fail) use ($request) {
                        $levelWilayah = $request->input('level_wilayah', '1');
                        if ($levelWilayah == '2' && (empty($value) || !preg_match('/^\d{2}$/', $value))) {
                            $fail('The kd_wilayah must be a two-digit string when level_wilayah is 2.');
                        }
                        if ($levelWilayah == '1' && !empty($value) && !preg_match('/^\d{2}$/', $value)) {
                            $fail('The kd_wilayah must be a two-digit string or empty when level_wilayah is 1.');
                        }
                    },
                ],
            ]);
        } catch (ValidationException $e) {
            Log::warning('Validation failed', ['errors' => $e->errors()]);
            if ($isApi) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            return [
                'title' => 'Inflasi',
                'errors' => array_merge(...array_values($e->errors())),
                'chart_status' => [],
                'chart_data' => []
            ];
        }

        // Default values
        $bulanTahunRecord = $this->resolveBulanTahun($validated['bulan'] ?? null, $validated['tahun'] ?? null);
        if (!$bulanTahunRecord) {
            $response = [
                'title' => 'Inflasi',
                'errors' => ['Bulan dan tahun aktif tidak ditemukan.'],
                'chart_status' => [],
                'chart_data' => []
            ];
            Log::warning('BulanTahun not found', ['bulan' => $validated['bulan'], 'tahun' => $validated['tahun']]);
            return $this->formatResponse($response, $isApi, 'error', 404);
        }

        $bulan = sprintf('%02d', $bulanTahunRecord->bulan);
        $tahun = $bulanTahunRecord->tahun;
        $level_wilayah = $validated['level_wilayah'] ?? '1';
        $kd_wilayah = $level_wilayah == '1' ? '0' : ($validated['kd_wilayah'] ?? '0');
        $kd_komoditas = $validated['kd_komoditas'] ?? '001';

        // Initialize response
        $response = [
            'title' => 'Inflasi',
            'bulan' => $bulan,
            'tahun' => $tahun,
            'level_wilayah' => $level_wilayah,
            'kd_wilayah' => $kd_wilayah,
            'kd_komoditas' => $kd_komoditas,
            'errors' => [],
            'chart_status' => [],
            'chart_data' => []
        ];

        Log::info('Request Inputs:', $response);

        // Get names
        $monthNames = $this->defaultMonthNames();
        $wilayahName = $level_wilayah == '1' ? 'Nasional' : Wilayah::where('kd_wilayah', $kd_wilayah)->value('nama_wilayah') ?? 'Unknown';
        $namaKomoditas = Komoditas::where('kd_komoditas', $kd_komoditas)->value('nama_komoditas') ?? 'Unknown';
        $monthName = $monthNames[$bulan] ?? $bulan;

        $response['title'] = trim("Inflasi Komoditas {$namaKomoditas} {$wilayahName} {$monthName} {$tahun}");

        // Define chart titles
        $chartTitles = $level_wilayah == '1' ? [
            'stackedLine' => "Tren Inflasi dan Andil {$monthName} {$tahun}",
            'horizontalBar' => "Perbandingan Inflasi dan Andil Antartingkat Harga {$monthName} {$tahun}",
            'heatmap' => "Inflasi per Provinsi Antartingkat Harga {$monthName} {$tahun}",
            'stackedBar' => "Distribusi Inflasi per Tingkat Harga {$monthName} {$tahun}",
            'provHorizontalBar' => "Inflasi per Provinsi {$monthName} {$tahun}",
            'kabkotHorizontalBar' => "Inflasi per Kabupaten/Kota {$monthName} {$tahun}",
            'provinsiChoropleth' => "Peta Inflasi Provinsi {$monthName} {$tahun}",
            'kabkotChoropleth' => "Peta Inflasi Kabupaten/Kota {$monthName} {$tahun}"
        ] : [
            'provinsiStackedLine' => "Tren Inflasi {$monthName} {$tahun}",
            'provinsiHorizontalBar' => "Perbandingan Inflasi {$monthName} {$tahun}",
            'provinsiKabkotHorizontalBar' => "Inflasi per Kabupaten/Kota {$monthName} {$tahun}",
            'provinsiKabkotChoropleth' => "Peta Inflasi Kabupaten/Kota {$monthName} {$tahun}"
        ];

        // Initialize chart status
        $response['chart_status'] = array_map(fn($title) => ['title' => $title, 'status' => 'not_applicable'], $chartTitles);

        // Fetch chart data
        $chartData = $level_wilayah == '1'
            ? $this->fetchNationalCharts($bulanTahunRecord->bulan_tahun_id, $kd_wilayah, $kd_komoditas)
            : $this->fetchProvincialCharts($bulanTahunRecord->bulan_tahun_id, $kd_wilayah, $kd_komoditas);

        // Update chart status and data
        foreach ($chartData['chart_status'] as $chart => $status) {
            if (isset($chartTitles[$chart])) {
                $response['chart_status'][$chart] = [
                    'title' => $chartTitles[$chart],
                    'status' => $status
                ];
            }
        }
        $response['chart_data'] = $chartData['chart_data'];
        $response['errors'] = array_merge($response['errors'], $chartData['errors']);

        Log::info('Chart Data Prepared:', ['chart_status' => $response['chart_status'], 'errors' => $response['errors']]);

        return $this->formatResponse($response, $isApi, empty($response['errors']) ? 'success' : 'partial');
    }

    /**
     * Format response as array or JsonResponse.
     *
     * @param array $response
     * @param bool $isApi
     * @param string $status
     * @param int $code
     * @return array|JsonResponse
     */
    private function formatResponse(array $response, bool $isApi, string $status, int $code = 200)
    {
        $response['message'] = $response['errors'] ? 'Beberapa data tidak tersedia.' : 'Data retrieved successfully';

        if ($isApi) {
            return response()->json([
                'message' => $response['message'],
                'status' => $status,
                'data' => $response
            ], $code);
        }

        return $response;
    }

    /**
     * Fetch data for national charts (8 charts).
     *
     * @param int $bulanTahunId
     * @param string $kd_wilayah
     * @param string $kd_komoditas
     * @return array
     */
    /**
     * Fetch data for national charts (8 charts).
     *
     * @param int $bulanTahunId
     * @param string $kd_wilayah
     * @param string $kd_komoditas
     * @return array
     */
    private function fetchNationalCharts(int $bulanTahunId, string $kd_wilayah, string $kd_komoditas): array
    {
        $bulanTahun = BulanTahun::find($bulanTahunId);
        if (!$bulanTahun) {
            return ['charts' => [], 'chart_status' => [], 'chart_data' => [], 'errors' => ['Bulan dan tahun tidak ditemukan.']];
        }

        $monthNames = $this->defaultMonthNames();
        $levelNames = $this->defaultLevelNames();
        $monthsData = $this->getPreviousMonths($bulanTahun->bulan, $bulanTahun->tahun, 5);

        if (empty($monthsData['ids'])) {
            return [
                'charts' => [],
                'chart_status' => [],
                'chart_data' => [],
                'errors' => ["Data untuk bulan {$monthNames[$bulanTahun->bulan]} tahun {$bulanTahun->tahun} tidak ditemukan."]
            ];
        }

        $charts = ['stackedLine', 'horizontalBar', 'heatmap', 'stackedBar', 'provHorizontalBar', 'kabkotHorizontalBar', 'provinsiChoropleth', 'kabkotChoropleth'];
        $chart_status = [];
        $chart_data = [];
        $errors = [];
        $hasCompleteData = true;

        // Stacked Line and Horizontal Bar (unchanged)
        $stackedLineData = ['xAxis' => [], 'series' => []];
        $horizontalBarData = ['labels' => [], 'datasets' => []];
        $summaryData = [];

        foreach ($monthsData['bulans'] as $m) {
            $stackedLineData['xAxis'][] = $monthNames[$m];
            $horizontalBarData['labels'][] = $monthNames[$m];
        }

        foreach ($levelNames as $kd => $name) {
            $inflasiData = [];
            $andilData = [];

            foreach ($monthsData['ids'] as $index => $id) {
                $record = Inflasi::where('bulan_tahun_id', $id)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_level', $kd)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('nilai_inflasi', 'andil')
                    ->first();

                $inflasiData[] = $record && !is_null($record->nilai_inflasi) ? (float)$record->nilai_inflasi : null;
                $andilData[] = $record && !is_null($record->andil) ? (float)$record->andil : null;

                if (!$record || is_null($record->nilai_inflasi)) {
                    $errors[] = "Data bulan {$monthNames[$monthsData['bulans'][$index]]} level {$name} tidak tersedia.";
                    $hasCompleteData = false;
                }
            }

            $stackedLineData['series'][] = ['name' => $name, 'data' => $inflasiData];
            $horizontalBarData['datasets'][] = [
                'label' => $name,
                'inflasi' => $inflasiData,
                'andil' => $andilData,
                'region' => $kd_wilayah,
                'region_name' => 'Nasional'
            ];
            $summaryData[$name] = ['inflasi' => end($inflasiData), 'andil' => end($andilData)];
        }

        $chart_data['stackedLine'] = $stackedLineData;
        $chart_data['horizontalBar'] = $horizontalBarData;
        $chart_data['summary'] = $summaryData;
        $chart_status['stackedLine'] = $chart_status['horizontalBar'] = $hasCompleteData ? 'complete' : 'incomplete';

        // Heatmap, Stacked Bar, and Choropleth
        $provinces = Wilayah::whereRaw('LENGTH(kd_wilayah) = 2')->pluck('nama_wilayah', 'kd_wilayah')->toArray();
        $kabkots = Wilayah::whereRaw('LENGTH(kd_wilayah) = 4')->pluck('nama_wilayah', 'kd_wilayah')->toArray();
        $kdLevels = array_keys($levelNames);
        $latestMonthId = $monthsData['ids'][0];

        $heatmapData = [
            'xAxis' => array_values($levelNames),
            'yAxis' => array_values($provinces),
            'values' => []
        ];
        $stackedBarData = [
            'labels' => array_values($levelNames),
            'datasets' => [
                ['label' => 'Menurun (<0)', 'data' => [], 'backgroundColor' => '#EE6666'],
                ['label' => 'Stabil (=0)', 'data' => [], 'backgroundColor' => '#FFCE34'],
                ['label' => 'Naik (>0)', 'data' => [], 'backgroundColor' => '#91CC75'],
                ['label' => 'Data tidak tersedia', 'data' => [], 'backgroundColor' => '#DCDDE2']
            ]
        ];
        $provHorizontalBarData = [];
        $kabkotHorizontalBarData = [];
        $provinsiChoroplethData = [];
        $kabkotChoroplethData = [];

        foreach ($kdLevels as $kdLevel) {
            $provInflasiComplete = true;
            $kabkotInflasiComplete = true;
            $missingProvs = [];
            $provRegions = array_keys($provinces);
            $provNames = array_values($provinces);
            $provInflasi = [];

            // Heatmap and Stacked Bar
            foreach ($provinces as $provKd => $provName) {
                $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                    ->where('kd_wilayah', $provKd)
                    ->where('kd_level', $kdLevel)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('nilai_inflasi')
                    ->first();

                $inflasi = $record && !is_null($record->nilai_inflasi) ? (float)$record->nilai_inflasi : null;
                $xIndex = array_search($kdLevel, $kdLevels);
                $yIndex = array_search($provName, $provNames);
                $heatmapData['values'][] = [$xIndex, $yIndex, $inflasi];

                $provInflasi[] = $inflasi;
                if (is_null($inflasi)) {
                    $missingProvs[] = $provName;
                    $provInflasiComplete = false;
                }
            }

            // Stacked Bar Counts
            $counts = ['menurun' => 0, 'stable' => 0, 'naik' => 0, 'na' => 0];
            foreach ($provInflasi as $inflasi) {
                if (is_null($inflasi)) $counts['na']++;
                elseif ($inflasi < 0) $counts['menurun']++;
                elseif ($inflasi == 0) $counts['stable']++;
                else $counts['naik']++;
            }
            $stackedBarData['datasets'][0]['data'][] = $counts['menurun'];
            $stackedBarData['datasets'][1]['data'][] = $counts['stable'];
            $stackedBarData['datasets'][2]['data'][] = $counts['naik'];
            $stackedBarData['datasets'][3]['data'][] = $counts['na'];

            // Provincial Horizontal Bar and Choropleth
            array_multisort(
                $provInflasi,
                SORT_ASC,
                SORT_NUMERIC,
                array_map(fn($val) => $val === null ? PHP_INT_MAX : 0, $provInflasi),
                SORT_ASC,
                $provRegions,
                $provNames
            );
            $provHorizontalBarData[] = [
                'kd_level' => $kdLevel,
                'regions' => $provRegions,
                'names' => $provNames,
                'inflasi' => $provInflasi
            ];
            // Add min and max for provinsiChoropleth per kd_level
            $provInflasiValues = array_filter($provInflasi, fn($val) => !is_null($val));
            $provinsiChoroplethData[] = [
                'kd_level' => $kdLevel,
                'regions' => $provRegions,
                'names' => $provNames,
                'inflasi' => $provInflasi,
                'min' => !empty($provInflasiValues) ? min($provInflasiValues) : null,
                'max' => !empty($provInflasiValues) ? max($provInflasiValues) : null
            ];

            // Kabkot Data (only for kd_level = '01')
            if ($kdLevel === '01') {
                $missingKabkots = [];
                $kabkotRegions = array_keys($kabkots);
                $kabkotNames = array_values($kabkots);
                $kabkotInflasi = array_map(
                    fn($kabKd) => Inflasi::where('bulan_tahun_id', $latestMonthId)
                        ->where('kd_wilayah', $kabKd)
                        ->where('kd_level', $kdLevel)
                        ->where('kd_komoditas', $kd_komoditas)
                        ->value('nilai_inflasi'),
                    $kabkotRegions
                );

                foreach ($kabkotInflasi as $index => $inflasi) {
                    if (is_null($inflasi)) {
                        $missingKabkots[] = $kabkotNames[$index];
                        $kabkotInflasiComplete = false;
                    }
                }

                array_multisort(
                    $kabkotInflasi,
                    SORT_ASC,
                    SORT_NUMERIC,
                    array_map(fn($val) => $val === null ? PHP_INT_MAX : 0, $kabkotInflasi),
                    SORT_ASC,
                    $kabkotRegions,
                    $kabkotNames
                );
                $kabkotHorizontalBarData[] = [
                    'kd_level' => $kdLevel,
                    'regions' => $kabkotRegions,
                    'names' => $kabkotNames,
                    'inflasi' => $kabkotInflasi
                ];
                // Add min and max for kabkotChoropleth
                $kabkotInflasiValues = array_filter($kabkotInflasi, fn($val) => !is_null($val));
                $kabkotChoroplethData[] = [
                    'kd_level' => $kdLevel,
                    'regions' => $kabkotRegions,
                    'names' => $kabkotNames,
                    'inflasi' => $kabkotInflasi,
                    'min' => !empty($kabkotInflasiValues) ? min($kabkotInflasiValues) : null,
                    'max' => !empty($kabkotInflasiValues) ? max($kabkotInflasiValues) : null
                ];

                if (!empty($missingKabkots)) {
                    $errors[] = 'Data kabupaten/kota ' . implode(', ', $missingKabkots) . ' tidak tersedia.';
                }
            }

            if (!empty($missingProvs)) {
                $errors[] = 'Data provinsi ' . implode(', ', $missingProvs) . ' tidak tersedia untuk level ' . $levelNames[$kdLevel] . '.';
            }
        }

        // Add min and max for heatmap
        $heatmapValues = array_filter(
            array_map(fn($val) => $val[2], $heatmapData['values']),
            fn($val) => !is_null($val)
        );
        $heatmapData['min'] = !empty($heatmapValues) ? min($heatmapValues) : null;
        $heatmapData['max'] = !empty($heatmapValues) ? max($heatmapValues) : null;

        $chart_data['heatmap'] = $heatmapData;
        $chart_data['stackedBar'] = $stackedBarData;
        $chart_data['provHorizontalBar'] = $provHorizontalBarData;
        $chart_data['kabkotHorizontalBar'] = $kabkotHorizontalBarData;
        $chart_data['provinsiChoropleth'] = $provinsiChoroplethData;
        $chart_data['kabkotChoropleth'] = $kabkotChoroplethData;

        $chart_status['heatmap'] = $chart_status['stackedBar'] = $chart_status['provHorizontalBar'] = $chart_status['provinsiChoropleth'] = $provInflasiComplete ? 'complete' : 'incomplete';
        $chart_status['kabkotHorizontalBar'] = $chart_status['kabkotChoropleth'] = $kabkotInflasiComplete ? 'complete' : 'incomplete';

        return [
            'charts' => $charts,
            'chart_status' => $chart_status,
            'chart_data' => $chart_data,
            'errors' => $errors
        ];
    }

    /**
     * Fetch data for provincial charts (4 charts).
     *
     * @param int $bulanTahunId
     * @param string $kd_wilayah
     * @param string $kd_komoditas
     * @return array
     */
    private function fetchProvincialCharts(int $bulanTahunId, string $kd_wilayah, string $kd_komoditas): array
    {
        $bulanTahun = BulanTahun::find($bulanTahunId);
        if (!$bulanTahun) {
            return ['charts' => [], 'chart_status' => [], 'chart_data' => [], 'errors' => ['Bulan dan tahun tidak ditemukan.']];
        }

        $monthNames = $this->defaultMonthNames();
        $levelNames = ['01' => 'Harga Konsumen Kota'];
        $monthsData = $this->getPreviousMonths($bulanTahun->bulan, $bulanTahun->tahun, 5);
        $wilayahName = Wilayah::where('kd_wilayah', $kd_wilayah)->value('nama_wilayah') ?? 'Unknown';

        if (empty($monthsData['ids'])) {
            return [
                'charts' => [],
                'chart_status' => [],
                'chart_data' => [],
                'errors' => ["Data untuk bulan {$monthNames[$bulanTahun->bulan]} tahun {$bulanTahun->tahun} tidak ditemukan."]
            ];
        }

        $charts = ['provinsiStackedLine', 'provinsiHorizontalBar', 'provinsiKabkotHorizontalBar', 'provinsiKabkotChoropleth'];
        $chart_status = [];
        $chart_data = [];
        $errors = [];
        $hasCompleteData = true;

        // Stacked Line and Horizontal Bar (unchanged)
        $stackedLineData = ['xAxis' => [], 'series' => []];
        $horizontalBarData = ['labels' => [], 'datasets' => []];
        $summaryData = [];

        foreach ($monthsData['bulans'] as $m) {
            $stackedLineData['xAxis'][] = $monthNames[$m];
            $horizontalBarData['labels'][] = $monthNames[$m];
        }

        foreach ($levelNames as $kd => $name) {
            $inflasiData = [];

            foreach ($monthsData['ids'] as $index => $id) {
                $record = Inflasi::where('bulan_tahun_id', $id)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_level', $kd)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('nilai_inflasi')
                    ->first();

                $inflasi = $record && !is_null($record->nilai_inflasi) ? (float)$record->nilai_inflasi : null;
                $inflasiData[] = $inflasi;

                if (is_null($inflasi)) {
                    $errors[] = "Data bulan {$monthNames[$monthsData['bulans'][$index]]} level {$name} tidak tersedia.";
                    $hasCompleteData = false;
                }
            }

            $stackedLineData['series'][] = ['name' => $name, 'data' => $inflasiData];
            $horizontalBarData['datasets'][] = [
                'label' => $name,
                'inflasi' => $inflasiData,
                'region' => $kd_wilayah,
                'region_name' => $wilayahName
            ];
            $summaryData[$name] = ['inflasi' => end($inflasiData)];
        }

        $chart_data['provinsiStackedLine'] = $stackedLineData;
        $chart_data['provinsiHorizontalBar'] = $horizontalBarData;
        $chart_data['summary'] = $summaryData;
        $chart_status['provinsiStackedLine'] = $chart_status['provinsiHorizontalBar'] = $hasCompleteData ? 'complete' : 'incomplete';

        // Kabkot Horizontal Bar and Choropleth
        $kabkots = Wilayah::where('parent_kd', $kd_wilayah)->pluck('nama_wilayah', 'kd_wilayah')->toArray();
        $latestMonthId = $monthsData['ids'][0];
        $kabkotHorizontalBarData = [];
        $kabkotChoroplethData = [];
        $hasCompleteKabkotData = true;

        if ($kabkots) {
            $kabkotRegions = array_keys($kabkots);
            $kabkotNames = array_values($kabkots);
            $kabkotInflasi = array_map(
                fn($kabKd) => Inflasi::where('bulan_tahun_id', $latestMonthId)
                    ->where('kd_wilayah', $kabKd)
                    ->where('kd_level', '01')
                    ->where('kd_komoditas', $kd_komoditas)
                    ->value('nilai_inflasi'),
                $kabkotRegions
            );

            $missingKabkots = [];
            foreach ($kabkotInflasi as $index => $inflasi) {
                if (is_null($inflasi)) {
                    $missingKabkots[] = $kabkotNames[$index];
                    $hasCompleteKabkotData = false;
                }
            }

            if (!empty($missingKabkots)) {
                $errors[] = 'Data kabupaten/kota ' . implode(', ', $missingKabkots) . ' tidak tersedia.';
            }

            array_multisort(
                $kabkotInflasi,
                SORT_ASC,
                SORT_NUMERIC,
                array_map(fn($val) => $val === null ? PHP_INT_MAX : 0, $kabkotInflasi),
                SORT_ASC,
                $kabkotRegions,
                $kabkotNames
            );

            $kabkotHorizontalBarData[] = [
                'kd_level' => '01',
                'regions' => $kabkotRegions,
                'names' => $kabkotNames,
                'inflasi' => $kabkotInflasi
            ];
            // Add min and max for provinsiKabkotChoropleth
            $kabkotInflasiValues = array_filter($kabkotInflasi, fn($val) => !is_null($val));
            $kabkotChoroplethData[] = [
                'kd_level' => '01',
                'regions' => $kabkotRegions,
                'names' => $kabkotNames,
                'inflasi' => $kabkotInflasi,
                'min' => !empty($kabkotInflasiValues) ? min($kabkotInflasiValues) : null,
                'max' => !empty($kabkotInflasiValues) ? max($kabkotInflasiValues) : null
            ];
        }

        $chart_data['provinsiKabkotHorizontalBar'] = $kabkotHorizontalBarData;
        $chart_data['provinsiKabkotChoropleth'] = $kabkotChoroplethData;
        $chart_status['provinsiKabkotHorizontalBar'] = $chart_status['provinsiKabkotChoropleth'] = $hasCompleteKabkotData ? 'complete' : 'incomplete';

        return [
            'charts' => $charts,
            'chart_status' => $chart_status,
            'chart_data' => $chart_data,
            'errors' => $errors
        ];
    }

    /**
     * Resolve BulanTahun record.
     *
     * @param string|null $bulan
     * @param string|null $tahun
     * @return BulanTahun|null
     */
    private function resolveBulanTahun(?string $bulan, ?string $tahun): ?BulanTahun
    {
        if ($bulan && $tahun) {
            return BulanTahun::where('bulan', sprintf('%02d', $bulan))
                ->where('tahun', $tahun)
                ->first();
        }
        return BulanTahun::where('aktif', 1)->first();
    }

    /**
     * Get previous months for historical data.
     *
     * @param string $bulan
     * @param string $tahun
     * @param int $count
     * @return array
     */
    private function getPreviousMonths(string $bulan, string $tahun, int $count): array
    {
        $ids = [];
        $bulans = [];
        $tahuns = [];
        $currentBulan = (int)$bulan;
        $currentTahun = (int)$tahun;

        for ($i = 0; $i < $count; $i++) {
            $bt = BulanTahun::where('bulan', sprintf('%02d', $currentBulan))
                ->where('tahun', $currentTahun)
                ->first();

            $ids[] = $bt ? $bt->bulan_tahun_id : null;
            $bulans[] = sprintf('%02d', $currentBulan);
            $tahuns[] = $currentTahun;

            $currentBulan = $currentBulan == 1 ? 12 : $currentBulan - 1;
            $currentTahun = $currentBulan == 12 ? $currentTahun - 1 : $currentTahun;
        }

        return [
            'ids' => array_reverse($ids),
            'bulans' => array_reverse($bulans),
            'tahuns' => array_reverse($tahuns)
        ];
    }

    /**
     * Default month names.
     *
     * @return array
     */
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

    /**
     * Default level names.
     *
     * @return array
     */
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
