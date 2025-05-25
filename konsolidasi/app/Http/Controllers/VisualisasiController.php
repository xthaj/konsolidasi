<?php

namespace App\Http\Controllers;

use App\Models\BulanTahun;
use App\Models\Inflasi;
use App\Models\Wilayah;
use App\Models\Komoditas;
use App\Models\LevelHarga;
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
            'kd_komoditas' => '000'
        ];

        $input = array_merge($defaults, $request->only(array_keys($defaults)));

        try {
            $validated = $request->validate([
                'bulan' => 'required|integer|between:1,12',
                'tahun' => 'required|integer|between:2000,2100',
                'level_wilayah' => 'required|in:1,2',
                'kd_komoditas' => 'nullable|string|max:3',
                'kd_wilayah' => [
                    'nullable',
                    function ($attribute, $value, $fail) use ($request) {
                        $levelWilayah = $request->input('level_wilayah', '1');
                        if ($levelWilayah == '2') {
                            if (empty($value) || !Wilayah::where('kd_wilayah', $value)->where('flag', 2)->exists()) {
                                $fail('kd_wilayah tidak valid.');
                            }
                        }
                        if ($levelWilayah == '1' && $value !== '0') {
                            $fail('Kode wilayah harus "0" untuk level nasional.');
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
                'errors' => ['Input tidak valid: ' . implode(', ', array_merge(...array_values($e->errors())))],
                'chart_status' => [],
                'chart_data' => []
            ];
        }

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

        $response = [
            'title' => 'Inflasi',
            'errors' => [],
            'chart_status' => [],
            'chart_data' => []
        ];

        $wilayahName = $level_wilayah == '1' ? 'Nasional' : Wilayah::where('kd_wilayah', $kd_wilayah)->value('nama_wilayah') ?? 'Unknown';
        $namaKomoditas = Komoditas::where('kd_komoditas', $kd_komoditas)->value('nama_komoditas') ?? 'Unknown';
        $monthName = BulanTahun::getBulanName($bulan);

        $response['title'] = trim("Inflasi Komoditas {$namaKomoditas} {$wilayahName} {$monthName} {$tahun}");

        $chartTitles = $level_wilayah == '1' ? [
            'line' => "Tren Inflasi dan Andil {$namaKomoditas} {$monthName} {$tahun}",
            'horizontalBar' => "Perbandingan Inflasi dan Andil Antartingkat Harga {$namaKomoditas} {$monthName} {$tahun}",
            'heatmap' => "Inflasi per Provinsi Antartingkat Harga {$namaKomoditas} {$monthName} {$tahun}",
            'stackedBar' => "Distribusi Inflasi per Tingkat Harga {$namaKomoditas} {$monthName} {$tahun}",
            'provHorizontalBar' => "Inflasi per Provinsi {$namaKomoditas} {$monthName} {$tahun}",
            'kabkotHorizontalBar' => "Inflasi per Kabupaten/Kota {$namaKomoditas} {$monthName} {$tahun}",
            'provinsiChoropleth' => "Peta Inflasi Provinsi {$namaKomoditas} {$monthName} {$tahun}",
            'kabkotChoropleth' => "Peta Inflasi Kabupaten/Kota {$namaKomoditas} {$monthName} {$tahun}"
        ] : [
            'line' => "Tren Inflasi {$namaKomoditas} {$monthName} {$tahun}",
            'horizontalBar' => "Perbandingan Inflasi {$namaKomoditas} {$monthName} {$tahun}",
            'kabkotHorizontalBar' => "Inflasi per Kabupaten/Kota {$namaKomoditas} {$monthName} {$tahun}",
            'kabkotChoropleth' => "Peta Inflasi Kabupaten/Kota {$namaKomoditas} {$monthName} {$tahun}"
        ];

        $response['chart_status'] = array_map(fn($title) => ['title' => $title, 'status' => 'not_applicable'], $chartTitles);

        $chartData = $level_wilayah == '1'
            ? $this->fetchNationalCharts($bulanTahunRecord->bulan_tahun_id, $kd_wilayah, $kd_komoditas)
            : $this->fetchProvincialCharts($bulanTahunRecord->bulan_tahun_id, $kd_wilayah, $kd_komoditas);

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

        // Define available kd_level values (based on original defaultLevelNames)
        $kdLevels = ['01', '02', '03', '04', '05'];
        $monthsData = $this->getPreviousMonths($bulanTahun->bulan, $bulanTahun->tahun, 5);

        if (empty($monthsData['ids'])) {
            return [
                'charts' => [],
                'chart_status' => [],
                'chart_data' => [],
                'errors' => ["Data untuk bulan " . BulanTahun::getBulanName($bulanTahun->bulan) . " tahun {$bulanTahun->tahun} tidak ditemukan."]
            ];
        }

        $finalInflasiMap = [];
        $errors = [];
        foreach ($monthsData['ids'] as $index => $id) {
            $record = Inflasi::where('bulan_tahun_id', $id)
                ->where('kd_wilayah', $kd_wilayah)
                ->where('kd_komoditas', '000')
                ->where('kd_level', '01')
                ->select('final_inflasi')
                ->first();
            $finalInflasiMap[$id] = $record && !is_null($record->final_inflasi) && is_numeric($record->final_inflasi);
            // Log::info('Final Inflasi Check', [
            //     'bulan_tahun_id' => $id,
            //     'month' => $monthsData['bulans'][$index],
            //     'year' => $monthsData['tahuns'][$index],
            //     'final_inflasi' => $finalInflasiMap[$id]
            // ]);
            if (!$finalInflasiMap[$id]) {
                $errors[] = "Data final tidak tersedia untuk Bulan " . BulanTahun::getBulanName($monthsData['bulans'][$index]) . " Tahun {$monthsData['tahuns'][$index]}.";
            }
        }
        // ADD: Log errors after final_inflasi check
        Log::info('Errors after Final Inflasi Check', ['errors' => $errors]);

        $charts = ['line', 'horizontalBar', 'heatmap', 'stackedBar', 'provHorizontalBar', 'kabkotHorizontalBar', 'provinsiChoropleth', 'kabkotChoropleth'];
        $chart_status = [];
        $chart_data = [];
        $hasCompleteData = true;

        // Line and Horizontal Bar
        $lineData = ['xAxis' => [], 'series' => []];
        $horizontalBarData = ['labels' => [], 'datasets' => []];
        $summaryData = [];

        foreach ($monthsData['bulans'] as $m) {
            $lineData['xAxis'][] = BulanTahun::getBulanName($m);
            $horizontalBarData['labels'][] = BulanTahun::getBulanName($m);
        }

        // Track missing levels for grouped error message
        $missingLevels = [];

        foreach ($kdLevels as $kd) {
            $name = LevelHarga::getLevelHargaNameComplete($kd);
            $inflasiData = [];
            $andilData = [];

            foreach ($monthsData['ids'] as $index => $id) {
                $record = Inflasi::where('bulan_tahun_id', $id)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_level', $kd)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('nilai_inflasi', 'andil', 'final_inflasi', 'final_andil')
                    ->first();

                // Use final_inflasi and final_andil if available
                $inflasi = $record && $finalInflasiMap[$id] && !is_null($record->final_inflasi)
                    ? (float)$record->final_inflasi
                    : ($record && !is_null($record->nilai_inflasi) ? (float)$record->nilai_inflasi : null);
                $andil = $record && $finalInflasiMap[$id] && !is_null($record->final_andil)
                    ? (float)$record->final_andil
                    : ($record && !is_null($record->andil) ? (float)$record->andil : null);

                $inflasiData[] = $inflasi;
                $andilData[] = $andil;

                if (!$record || is_null($inflasi) || is_null($andil)) {
                    $missingLevels[$id][$kd] = [
                        'name' => $name,
                        'month' => $monthsData['bulans'][$index],
                        'year' => $monthsData['tahuns'][$index]
                    ];
                    $hasCompleteData = false;
                }
            }

            $lineData['series'][] = [
                'name' => $name,
                'inflasi' => $inflasiData,
                'andil' => $andilData
            ];
            $horizontalBarData['datasets'][] = [
                'label' => $name,
                'inflasi' => $inflasiData,
                'andil' => $andilData,
                'region' => $kd_wilayah,
                'region_name' => 'Nasional'
            ];
            $summaryData[$name] = ['inflasi' => end($inflasiData), 'andil' => end($andilData)];
        }

        // Grouped error message for missing levels
        foreach ($missingLevels as $id => $levels) {
            foreach ($levels as $data) {
                $errors[] = "Data untuk level harga {$data['name']} di " . BulanTahun::getBulanName($data['month']) . " {$data['year']} tidak tersedia.";
            }
        }

        $chart_data['line'] = $lineData;
        $chart_data['horizontalBar'] = $horizontalBarData;
        $chart_data['summary'] = $summaryData;
        $chart_status['line'] = $chart_status['horizontalBar'] = $hasCompleteData ? 'complete' : 'incomplete';

        // Heatmap, Stacked Bar, and Choropleth
        $provinces = Wilayah::where('flag', 2)->pluck('nama_wilayah', 'kd_wilayah')->toArray();
        $kabkots = Wilayah::where('flag', 3)->pluck('nama_wilayah', 'kd_wilayah')->toArray();
        $latestMonthId = $monthsData['ids'][0];

        $useFinalInflasi = $finalInflasiMap[$latestMonthId];

        $heatmapData = [
            'xAxis' => array_map(fn($kd) => LevelHarga::getLevelHargaNameShortened($kd), $kdLevels),
            'yAxis' => array_values($provinces),
            'values' => []
        ];
        $stackedBarData = [
            'labels' => array_map(fn($kd) => LevelHarga::getLevelHargaNameShortened($kd), $kdLevels),
            'datasets' => [
                ['label' => 'Menurun (<0)', 'data' => []],
                ['label' => 'Stabil (=0)', 'data' => []],
                ['label' => 'Naik (>0)', 'data' => []],
                ['label' => 'Data tidak tersedia', 'data' => []],
            ]
        ];
        $provHorizontalBarData = [];
        $kabkotHorizontalBarData = [];
        $provinsiChoroplethData = [];
        $kabkotChoroplethData = [];

        // Track missing regions for grouped error message
        $missingRegions = [];
        foreach ($kdLevels as $kdLevel) {
            $provInflasiComplete = true;
            $kabkotInflasiComplete = true;
            $provRegions = array_keys($provinces);
            $provNames = array_values($provinces);
            $provInflasi = [];

            foreach ($provinces as $provKd => $provName) {
                $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                    ->where('kd_wilayah', $provKd)
                    ->where('kd_level', $kdLevel)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('nilai_inflasi', 'final_inflasi')
                    ->first();

                // Use final_inflasi if available
                $inflasi = $record && $useFinalInflasi && !is_null($record->final_inflasi)
                    ? (float)$record->final_inflasi
                    : ($record && !is_null($record->nilai_inflasi) ? (float)$record->nilai_inflasi : null);
                $xIndex = array_search($kdLevel, $kdLevels);
                $yIndex = array_search($provName, $provNames);
                $heatmapData['values'][] = [$xIndex, $yIndex, $inflasi];

                $provInflasi[] = $inflasi;
                if (is_null($inflasi)) {
                    // MODIFY: Store province name for specific error
                    $missingRegions[$latestMonthId][$kdLevel][] = $provName;
                    $provInflasiComplete = false;
                }
            }

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
            $provInflasiValues = array_filter($provInflasi, fn($val) => !is_null($val));
            $provinsiChoroplethData[] = [
                'kd_level' => $kdLevel,
                'regions' => $provRegions,
                'names' => $provNames,
                'inflasi' => $provInflasi,
                'min' => !empty($provInflasiValues) ? min($provInflasiValues) : null,
                'max' => !empty($provInflasiValues) ? max($provInflasiValues) : null
            ];

            if ($kdLevel === '01') {
                $kabkotRegions = array_keys($kabkots);
                $kabkotNames = array_values($kabkots);
                $kabkotInflasi = array_map(
                    fn($kabKd) => Inflasi::where('bulan_tahun_id', $latestMonthId)
                        ->where('kd_wilayah', $kabKd)
                        ->where('kd_level', '01')
                        ->where('kd_komoditas', $kd_komoditas)
                        ->value($useFinalInflasi ? 'final_inflasi' : 'nilai_inflasi'),
                    $kabkotRegions
                );

                foreach ($kabkotInflasi as $index => $inflasi) {
                    if (is_null($inflasi)) {
                        $missingRegions[$latestMonthId][$kdLevel][] = $kabkotNames[$index];
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
                    'kd_level' => '01',
                    'regions' => $kabkotRegions,
                    'names' => $kabkotNames,
                    'inflasi' => $kabkotInflasi
                ];
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
        }

        foreach ($missingRegions as $id => $levels) {
            foreach ($levels as $kdLevel => $regionNames) {
                if (!empty($regionNames)) {
                    $regionNames = array_unique($regionNames); // Avoid duplicates
                    $levelName = LevelHarga::getLevelHargaNameComplete($kdLevel);
                    $monthIndex = array_search($id, $monthsData['ids']);
                    $month = $monthsData['bulans'][$monthIndex];
                    $year = $monthsData['tahuns'][$monthIndex];
                    $errors[] = "Data untuk wilayah " . implode(', ', $regionNames) . " pada level harga {$levelName} di " . BulanTahun::getBulanName($month) . " {$year} tidak tersedia.";
                }
            }
        }

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

        $levelNames = ['01' => LevelHarga::getLevelHargaNameComplete('01')];
        $monthsData = $this->getPreviousMonths($bulanTahun->bulan, $bulanTahun->tahun, 5);
        $wilayahName = Wilayah::where('kd_wilayah', $kd_wilayah)->value('nama_wilayah') ?? 'Unknown';

        if (empty($monthsData['ids'])) {
            return [
                'charts' => [],
                'chart_status' => [],
                'chart_data' => [],
                'errors' => ["Data untuk bulan " . BulanTahun::getBulanName($bulanTahun->bulan) . " tahun {$bulanTahun->tahun} tidak ditemukan."]
            ];
        }

        // Check final_inflasi for kd_komoditas = '000' for all relevant bulan_tahun_id
        $finalInflasiMap = [];
        $errors = [];
        foreach ($monthsData['ids'] as $index => $id) {
            $record = Inflasi::where('bulan_tahun_id', $id)
                ->where('kd_wilayah', $kd_wilayah)
                ->where('kd_komoditas', '000')
                ->where('kd_level', '01')
                ->select('final_inflasi')
                ->first();
            $finalInflasiMap[$id] = $record && !is_null($record->final_inflasi) && is_numeric($record->final_inflasi);
            Log::info('Final Inflasi Check', [
                'bulan_tahun_id' => $id,
                'month' => $monthsData['bulans'][$index],
                'year' => $monthsData['tahuns'][$index],
                'final_inflasi' => $finalInflasiMap[$id]
            ]);
            if (!$finalInflasiMap[$id]) {
                $errors[] = "Data final tidak tersedia untuk Bulan " . BulanTahun::getBulanName($monthsData['bulans'][$index]) . " Tahun {$monthsData['tahuns'][$index]}.";
            }
        }

        $charts = ['line', 'horizontalBar', 'kabkotHorizontalBar', 'kabkotChoropleth'];
        $chart_status = [];
        $chart_data = [];

        $hasCompleteData = true;

        // Line and Horizontal Bar
        $lineData = ['xAxis' => [], 'series' => []];
        $horizontalBarData = ['labels' => [], 'datasets' => []];
        $summaryData = [];

        foreach ($monthsData['bulans'] as $m) {
            $lineData['xAxis'][] = BulanTahun::getBulanName($m);
            $horizontalBarData['labels'][] = BulanTahun::getBulanName($m);
        }

        // Track missing levels for grouped error message
        $missingLevels = [];
        foreach ($levelNames as $kd => $name) {
            $inflasiData = [];

            foreach ($monthsData['ids'] as $index => $id) {
                $record = Inflasi::where('bulan_tahun_id', $id)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_level', $kd)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('nilai_inflasi', 'final_inflasi')
                    ->first();

                $inflasi = $record && $finalInflasiMap[$id] && !is_null($record->final_inflasi)
                    ? (float)$record->final_inflasi
                    : ($record && !is_null($record->nilai_inflasi) ? (float)$record->nilai_inflasi : null);

                $inflasiData[] = $inflasi;

                if (is_null($inflasi)) {
                    $missingLevels[$id][$kd] = [
                        'name' => $name,
                        'month' => $monthsData['bulans'][$index],
                        'year' => $monthsData['tahuns'][$index]
                    ];
                    $hasCompleteData = false;
                }
            }

            $lineData['series'][] = [
                'name' => $name,
                'inflasi' => $inflasiData,
            ];
            $horizontalBarData['datasets'][] = [
                'label' => $name,
                'inflasi' => $inflasiData,
                'region' => $kd_wilayah,
                'region_name' => $wilayahName
            ];
            $summaryData[$name] = ['inflasi' => end($inflasiData)];
        }

        foreach ($missingLevels as $id => $levels) {
            foreach ($levels as $data) {
                $errors[] = "Data untuk level harga {$data['name']} di " . BulanTahun::getBulanName($data['month']) . " {$data['year']} tidak tersedia.";
            }
        }


        $chart_data['line'] = $lineData;
        $chart_data['horizontalBar'] = $horizontalBarData;
        $chart_data['summary'] = $summaryData;
        $chart_status['line'] = $chart_status['horizontalBar'] = $hasCompleteData ? 'complete' : 'incomplete';

        // Kabkot Horizontal Bar and Choropleth
        $kabkots = Wilayah::where('flag', 3)->where('parent_kd', $kd_wilayah)->pluck('nama_wilayah', 'kd_wilayah')->toArray();
        $latestMonthId = $monthsData['ids'][0];

        $useFinalInflasi = $finalInflasiMap[$latestMonthId];
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
                    ->value($useFinalInflasi ? 'final_inflasi' : 'nilai_inflasi'),
                $kabkotRegions
            );

            $missingKabkots = [];
            foreach ($kabkotInflasi as $index => $inflasi) {
                if (is_null($inflasi)) {
                    $missingKabkots[] = $kabkotNames[$index];
                    $hasCompleteKabkotData = false;
                }
            }

            foreach ($missingKabkots as $id => $levels) {
                if (!empty($regionNames)) {
                    $regionNames = array_unique($regionNames);
                    $levelName = "Harga Konsumen Kota";
                    $monthIndex = array_search($id, $monthsData['ids']);
                    $month = $monthsData['bulans'][$monthIndex];
                    $year = $monthsData['tahuns'][$monthIndex];
                    $errors[] = "Data untuk kabupaten/kota " . implode(', ', $regionNames) . " pada level harga {$levelName} di " . BulanTahun::getBulanName($month) . " {$year} tidak tersedia.";
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
                'kd_level' => '01',
                'regions' => $kabkotRegions,
                'names' => $kabkotNames,
                'inflasi' => $kabkotInflasi
            ];
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

        $chart_data['kabkotHorizontalBar'] = $kabkotHorizontalBarData;
        $chart_data['kabkotChoropleth'] = $kabkotChoroplethData;
        $chart_status['kabkotHorizontalBar'] = $chart_status['kabkotChoropleth'] = $hasCompleteKabkotData ? 'complete' : 'incomplete';

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
}
