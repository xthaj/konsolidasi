<?php

namespace App\Http\Controllers;

use App\Models\BulanTahun;
use App\Models\Inflasi;
use App\Models\Wilayah;
use App\Models\Komoditas;
use App\Models\LevelHarga;
use App\Models\User;
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
    public function create(): View
    {
        return view('visualisasi.harmonisasi', [
            'isPusat' => auth()->user()->isPusat()
        ]);
    }

    /**
     * Fetch visualization data for charts.
     *
     * @param Request $request
     * @param bool $isApi
     * @return array|JsonResponse
     */
    public function fetchVisualisasiData(Request $request): JsonResponse
    {
        try {
            $activeBulanTahun = BulanTahun::where('aktif', 1)->first();
            if (!$activeBulanTahun) {
                $response = [
                    'title' => 'Inflasi',
                    'errors' => ['Tidak ada periode aktif.'],
                    'chart_status' => [],
                    'chart_data' => []
                ];
                Log::warning('No active period found');

                return response()->json([
                    'message' => 'Beberapa data tidak tersedia: Tidak ada periode aktif.',
                    'data' => $response
                ], 200);
            }

            $defaults = [
                'bulan' => $activeBulanTahun->bulan,
                'tahun' => $activeBulanTahun->tahun,
                'kd_komoditas' => auth()->user()->isPusat() ? 0 : 1
            ];

            $input = array_merge($defaults, $request->only(array_keys($defaults)));

            try {
                $validated = $request->validate([
                    'bulan' => 'required|integer|between:1,12',
                    'tahun' => 'required|integer|between:2000,2100',
                    'kd_komoditas' => 'nullable|string|max:3',
                ]);
            } catch (ValidationException $e) {
                Log::warning('Validation failed', ['errors' => $e->errors()]);
                // edit here: Include validation errors in message and keep data structure
                return response()->json([
                    'message' => 'Validation failed: ' . implode(', ', array_merge(...array_values($e->errors()))),
                    'data' => ['title' => 'Inflasi', 'errors' => $e->errors(), 'chart_status' => [], 'chart_data' => []]
                ], 422);
            }

            $bulanTahunRecord = $this->resolveBulanTahun($validated['bulan'] ?? null, $validated['tahun'] ?? null);
            if (!$bulanTahunRecord) {
                $response = [
                    'title' => 'Inflasi',
                    'errors' => ['Belum ada data di periode terpilih.'],
                    'chart_status' => [],
                    'chart_data' => []
                ];
                Log::warning('BulanTahun not found', ['bulan' => $validated['bulan'], 'tahun' => $validated['tahun']]);
                return response()->json([
                    'message' => 'Beberapa data tidak tersedia: Belum ada data di periode terpilih.',
                    'data' => $response
                ], 200);
            }

            $bulan = sprintf('%02d', $bulanTahunRecord->bulan);
            $tahun = $bulanTahunRecord->tahun;
            $kd_wilayah = 0;
            // $kd_komoditas = $validated['kd_komoditas'] ?? $defaults['kd_komoditas'];
            $kd_komoditas = $validated['kd_komoditas'] ?? $defaults['kd_komoditas'];
            if ($kd_komoditas == 0 && !auth()->user()->isPusat()) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses untuk melihat data komoditas umum.',
                    'data' => null
                ], 403);
            }

            $response = [
                'title' => 'Inflasi',
                'errors' => [],
                'chart_status' => [],
                'chart_data' => []
            ];

            $wilayahName = 'Nasional';
            $namaKomoditas = Komoditas::where('kd_komoditas', $kd_komoditas)->value('nama_komoditas') ?? 'Unknown';
            $monthName = BulanTahun::getBulanName($bulan);

            $response['title'] = trim("Inflasi Komoditas {$namaKomoditas} {$wilayahName} {$monthName} {$tahun}");

            $chartTitles = [
                'line' => "Tren Inflasi dan Andil {$namaKomoditas} {$monthName} {$tahun}",
                'horizontalBar' => "Perbandingan Inflasi dan Andil Antartingkat Harga {$namaKomoditas} {$monthName} {$tahun}",
                'heatmap' => "Inflasi per Provinsi Antartingkat Harga {$namaKomoditas} {$monthName} {$tahun}",
                'stackedBar' => "Distribusi Provinsi Menurut Inflasi per Tingkat Harga {$namaKomoditas} {$monthName} {$tahun}",
                'provHorizontalBar' => "Inflasi per Provinsi {$namaKomoditas} {$monthName} {$tahun}",
                'kabkotHorizontalBar' => "Inflasi per Kabupaten/Kota {$namaKomoditas} {$monthName} {$tahun}",
                'provinsiChoropleth' => "Peta Inflasi Provinsi {$namaKomoditas} {$monthName} {$tahun}",
                'kabkotChoropleth' => "Peta Inflasi Kabupaten/Kota {$namaKomoditas} {$monthName} {$tahun}"
            ];

            $response['chart_status'] = array_map(fn($title) => ['title' => $title, 'status' => 'not_applicable'], $chartTitles);

            $chartData = $this->fetchNationalCharts($bulanTahunRecord->bulan_tahun_id, $kd_wilayah, $kd_komoditas);

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

            Log::info('Chart data prepared', [
                'charts' => count($response['chart_status']),
                'complete' => collect($response['chart_status'])->where('status', 'complete')->count(),
                'errors' => count($response['errors']),
            ]);

            $message = empty($response['errors'])
                ? 'Data berhasil diambil'
                : 'Beberapa data tidak tersedia: ' . implode(', ', $response['errors']);
            return response()->json([
                'message' => $message,
                'data' => $response
            ], 200);
        } catch (\Exception $e) {
            // Catch unexpected errors and include in message
            Log::error('Unexpected error in fetchVisualisasiData', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memuat data: ' . $e->getMessage(),
                'data' => ['title' => 'Inflasi', 'errors' => ['Terjadi kesalahan server.'], 'chart_status' => [], 'chart_data' => []]
            ], 500);
        }
    }


    /**
     * Fetches national inflation chart data for various chart types based on the specified month-year, region, and commodity.
     *
     * @param int $bulanTahunId The ID of the BulanTahun (month-year) record.
     * @param string $kd_wilayah The region code (e.g., national or specific region).
     * @param string $kd_komoditas The commodity code (e.g., '000' for general inflation).
     * @return array An array containing chart types, their status, data, and any errors.
     */
    private function fetchNationalCharts(int $bulanTahunId, string $kd_wilayah, string $kd_komoditas): array
    {
        try {
            // Step 1: Validate the BulanTahun record exists
            $bulanTahun = BulanTahun::find($bulanTahunId);
            if (!$bulanTahun) {
                // Validation: If the month-year ID is invalid, return empty data with an error message
                return [
                    'charts' => [],
                    'chart_status' => [],
                    'chart_data' => [],
                    'errors' => ['Bulan dan tahun tidak ditemukan.']
                ];
            }

            // Step 2: Define available price levels (e.g., Consumer Price, Wholesale, etc.)
            $kdLevels = ['01', '02', '03', '04', '05'];

            // Step 3: Get data for the specified month and the previous 4 months (total 5 months)
            $monthsData = $this->getPreviousMonths($bulanTahun->bulan, $bulanTahun->tahun, 5);

            // Validation: Check if monthsData contains valid IDs
            if (empty($monthsData['ids'])) {
                return [
                    'charts' => [],
                    'chart_status' => [],
                    'chart_data' => [],
                    'errors' => [
                        "Data untuk bulan " . BulanTahun::getBulanName($bulanTahun->bulan) . " tahun {$bulanTahun->tahun} tidak ditemukan."
                    ]
                ];
            }

            // Step 4: Check availability of final_inflasi for each month
            // CHANGED: fetch all months in ONE query instead of 1 query per month
            $finalInflasiRecords = Inflasi::whereIn('bulan_tahun_id', $monthsData['ids'])
                ->where('kd_wilayah', $kd_wilayah)
                ->where('kd_komoditas', '0')
                ->where('kd_level', '01')
                ->select('bulan_tahun_id', 'final_inflasi')
                ->get()
                ->keyBy('bulan_tahun_id');

            $finalInflasiMap = [];
            $errors = [];
            foreach ($monthsData['ids'] as $index => $id) {
                // CHANGED: lookup from collection instead of querying DB
                $record = $finalInflasiRecords[$id] ?? null;

                // Validation: Check if final_inflasi is available and numeric
                $finalInflasiMap[$id] = $record && !is_null($record->final_inflasi) && is_numeric($record->final_inflasi);

                if (!$finalInflasiMap[$id]) {
                    // If final_inflasi is missing, add an error for the specific month
                    $errors[] = "Data final tidak tersedia untuk Bulan " .
                        BulanTahun::getBulanName($monthsData['bulans'][$index]) .
                        " Tahun {$monthsData['tahuns'][$index]}.";
                }
            }

            // Log errors after checking final_inflasi availability
            if (!empty($errors)) {
                Log::info('Final Inflasi Check errors', ['count' => count($errors), 'sample' => array_slice($errors, 0, 3)]);
            }

            // Step 5: Initialize chart types and data structures
            $charts = ['line', 'horizontalBar', 'heatmap', 'stackedBar', 'provHorizontalBar', 'kabkotHorizontalBar', 'provinsiChoropleth', 'kabkotChoropleth'];
            $chart_status = [];
            $chart_data = [];
            $hasCompleteData = true; // Flag to track if all data for line and horizontalBar charts is complete

            // Step 6: Prepare data for Line and Horizontal Bar charts
            $lineData = ['xAxis' => [], 'series' => []];
            $horizontalBarData = ['labels' => [], 'datasets' => []];
            $summaryData = [];

            // Populate xAxis and labels with month names
            foreach ($monthsData['bulans'] as $m) {
                $lineData['xAxis'][] = BulanTahun::getBulanName($m);
                $horizontalBarData['labels'][] = BulanTahun::getBulanName($m);
            }

            // Track missing levels for error reporting
            $missingLevels = [];

            // Step 7: Fetch inflation and contribution (andil) data for each price level
            // CHANGED: fetch all months x levels in ONE query instead of 1 query per month per level
            $trendInflasiRecords = Inflasi::whereIn('bulan_tahun_id', $monthsData['ids'])
                ->where('kd_wilayah', $kd_wilayah)
                ->whereIn('kd_level', $kdLevels)
                ->where('kd_komoditas', $kd_komoditas)
                ->select('bulan_tahun_id', 'kd_level', 'nilai_inflasi', 'andil', 'final_inflasi', 'final_andil')
                ->get()
                ->keyBy(fn($item) => "{$item->bulan_tahun_id}-{$item->kd_level}");

            foreach ($kdLevels as $kd) {
                $name = LevelHarga::getLevelHargaNameComplete($kd);
                $inflasiData = [];
                $andilData = [];

                foreach ($monthsData['ids'] as $index => $id) {
                    // CHANGED: lookup from collection instead of querying DB
                    $record = $trendInflasiRecords["{$id}-{$kd}"] ?? null;

                    // Validation: Prefer final_inflasi/final_andil if available and valid
                    $inflasi = $record && $finalInflasiMap[$id] && !is_null($record->final_inflasi)
                        ? $record->final_inflasi
                        : ($record && !is_null($record->nilai_inflasi) ? $record->nilai_inflasi : null);
                    $andil = $record && $finalInflasiMap[$id] && !is_null($record->final_andil)
                        ? $record->final_andil
                        : ($record && !is_null($record->andil) ? $record->andil : null);

                    $inflasiData[] = is_numeric($inflasi) ? number_format($inflasi, 2, '.', '') : null;
                    $andilData[] = is_numeric($andil) ? number_format($andil, 2, '.', '') : null;

                    // Validation: Mark data as incomplete if either inflasi or andil is missing
                    if (!$record || is_null($inflasi) || is_null($andil)) {
                        $missingLevels[$id][$kd] = [
                            'name' => $name,
                            'month' => $monthsData['bulans'][$index],
                            'year' => $monthsData['tahuns'][$index]
                        ];
                        $hasCompleteData = false;
                    }
                }

                // Populate line and horizontalBar chart data
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

            // Step 8: Generate grouped error messages for missing levels
            foreach ($missingLevels as $id => $levels) {
                foreach ($levels as $data) {
                    $errors[] = "Data untuk level harga {$data['name']} di " .
                        BulanTahun::getBulanName($data['month']) . " {$data['year']} tidak tersedia.";
                }
            }

            // Assign data and status for line and horizontalBar charts
            $chart_data['line'] = $lineData;
            $chart_data['horizontalBar'] = $horizontalBarData;
            $chart_data['summary'] = $summaryData;
            $chart_status['line'] = $chart_status['horizontalBar'] = $hasCompleteData ? 'complete' : 'incomplete';

            // Step 9: Prepare data for Heatmap, Stacked Bar, and Choropleth charts
            $provinces = Wilayah::where('flag', 2)
                ->pluck('nama_wilayah', 'kd_wilayah')
                ->toArray();

            $kabkots = Wilayah::where('flag', 3)
                ->where('inflasi_tracked', 1)
                ->pluck('nama_wilayah', 'kd_wilayah')
                ->toArray();

            $latestMonthId = $bulanTahunId;

            // Determine if final_inflasi should be used for the latest month
            $useFinalInflasi = $finalInflasiMap[$latestMonthId];

            // CHANGED: fetch all provinces x levels in ONE query instead of 1 query per province per level
            $provinceInflasiRecords = Inflasi::where('bulan_tahun_id', $latestMonthId)
                ->whereIn('kd_wilayah', array_keys($provinces))
                ->whereIn('kd_level', $kdLevels)
                ->where('kd_komoditas', $kd_komoditas)
                ->select('kd_wilayah', 'kd_level', 'nilai_inflasi', 'final_inflasi')
                ->get()
                ->keyBy(fn($item) => "{$item->kd_wilayah}-{$item->kd_level}");

            // CHANGED: fetch all kabkot inflasi in ONE query instead of 1 query per kabkot
            $kabkotInflasiRecords = Inflasi::where('bulan_tahun_id', $latestMonthId)
                ->whereIn('kd_wilayah', array_keys($kabkots))
                ->where('kd_level', '01')
                ->where('kd_komoditas', $kd_komoditas)
                ->select('kd_wilayah', 'nilai_inflasi', 'final_inflasi')
                ->get()
                ->keyBy('kd_wilayah');

            // Initialize heatmap data structure
            $heatmapData = [
                'xAxis' => array_map(fn($kd) => LevelHarga::getLevelHargaNameShortened($kd), $kdLevels),
                'yAxis' => array_values($provinces),
                'values' => []
            ];
            // Initialize stackedBar data structure
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

            // Track missing regions for error reporting
            $missingRegions = [];
            foreach ($kdLevels as $kdLevel) {
                $provInflasiComplete = true; // Flag for province data completeness
                $kabkotInflasiComplete = true; // Flag for kabkot data completeness
                $provRegions = array_keys($provinces);
                $provNames = array_values($provinces);
                $provInflasi = [];

                // Step 10: Fetch province-level inflation data
                foreach ($provinces as $provKd => $provName) {
                    // CHANGED: lookup from collection instead of querying DB
                    $record = $provinceInflasiRecords["{$provKd}-{$kdLevel}"] ?? null;

                    // Validation: Prefer final_inflasi if available
                    $inflasi = $record && $useFinalInflasi && !is_null($record->final_inflasi)
                        ? $record->final_inflasi
                        : ($record && !is_null($record->nilai_inflasi) ? $record->nilai_inflasi : null);

                    // Add data to heatmap
                    $xIndex = array_search($kdLevel, $kdLevels);
                    $yIndex = array_search($provName, $provNames);
                    $heatmapData['values'][] = [$xIndex, $yIndex, is_numeric($inflasi) ? number_format($inflasi, 2, '.', '') : null];

                    $provInflasi[] = is_numeric($inflasi) ? number_format($inflasi, 2, '.', '') : null;

                    if (is_null($inflasi)) {
                        // Track missing province data
                        $missingRegions[$latestMonthId][$kdLevel][] = $provName;
                        $provInflasiComplete = false;
                    }
                }

                // Step 11: Count inflation trends for stackedBar
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

                // Step 12: Sort province data for provHorizontalBar and provinsiChoropleth
                array_multisort(
                    $provInflasi,
                    SORT_ASC,
                    SORT_NUMERIC,
                    array_map(fn($val) => $val === null ? PHP_INT_MAX : 0, $provInflasi),
                    SORT_ASC,
                    $provRegions,
                    SORT_DESC,
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

                // Step 13: Fetch kabkot data (only for kd_level '01')
                if ($kdLevel === '01') {
                    $kabkotRegions = array_keys($kabkots);
                    $kabkotNames = array_values($kabkots);
                    $kabkotInflasi = array_map(function ($kabKd) use ($kabkotInflasiRecords, $useFinalInflasi) {
                        // CHANGED: lookup from collection instead of querying DB
                        $record = $kabkotInflasiRecords[$kabKd] ?? null;
                        $value = $record
                            ? ($useFinalInflasi ? $record->final_inflasi : $record->nilai_inflasi)
                            : null;
                        return is_numeric($value) ? number_format($value, 2, '.', '') : null;
                    }, $kabkotRegions);

                    // Validation: Check for missing kabkot data
                    foreach ($kabkotInflasi as $index => $inflasi) {
                        if (is_null($inflasi)) {
                            $missingRegions[$latestMonthId][$kdLevel][] = $kabkotNames[$index];
                            $kabkotInflasiComplete = false;
                        }
                    }

                    // Sort kabkot data
                    array_multisort(
                        $kabkotInflasi,
                        SORT_ASC,
                        SORT_NUMERIC,
                        array_map(fn($val) => $val === null ? PHP_INT_MAX : 0, $kabkotInflasi),
                        SORT_ASC,
                        $kabkotRegions,
                        SORT_DESC,
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

                // Assign status for province and kabkot charts
                $chart_status['heatmap'] = $chart_status['stackedBar'] =
                    $chart_status['provHorizontalBar'] = $chart_status['provinsiChoropleth'] =
                    $provInflasiComplete ? 'complete' : 'incomplete';
                $chart_status['kabkotHorizontalBar'] = $chart_status['kabkotChoropleth'] =
                    $kabkotInflasiComplete ? 'complete' : 'incomplete';
            }

            // Step 14: Generate grouped error messages for missing regions
            foreach ($missingRegions as $id => $levels) {
                foreach ($levels as $kdLevel => $regionNames) {
                    if (!empty($regionNames)) {
                        $regionNames = array_unique($regionNames); // Avoid duplicates
                        $levelName = LevelHarga::getLevelHargaNameComplete($kdLevel);
                        $monthIndex = array_search($id, $monthsData['ids']);
                        $month = $monthsData['bulans'][$monthIndex];
                        $year = $monthsData['tahuns'][$monthIndex];
                        $errors[] = "Data untuk wilayah " . implode(', ', $regionNames) .
                            " pada level harga {$levelName} di " .
                            BulanTahun::getBulanName($month) . " {$year} tidak tersedia.";
                    }
                }
            }

            // Step 15: Calculate min/max for heatmap
            $heatmapValues = array_filter(
                array_map(fn($val) => $val[2], $heatmapData['values']),
                fn($val) => !is_null($val)
            );
            $heatmapData['min'] = !empty($heatmapValues) ? min($heatmapValues) : null;
            $heatmapData['max'] = !empty($heatmapValues) ? max($heatmapValues) : null;

            // Step 16: Assign data to chart_data
            $chart_data['heatmap'] = $heatmapData;
            $chart_data['stackedBar'] = $stackedBarData;
            $chart_data['provHorizontalBar'] = $provHorizontalBarData;
            $chart_data['kabkotHorizontalBar'] = $kabkotHorizontalBarData;
            $chart_data['provinsiChoropleth'] = $provinsiChoroplethData;
            $chart_data['kabkotChoropleth'] = $kabkotChoroplethData;

            // Step 17: Return the final result
            return [
                'charts' => $charts,
                'chart_status' => $chart_status,
                'chart_data' => $chart_data,
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            // Catch unexpected errors and include in message
            Log::error('Unexpected error in fetchNationalCharts', ['error' => $e->getMessage()]);
            return [
                'charts' => [],
                'chart_status' => [],
                'chart_data' => [],
                'errors' => ['Gagal memuat data nasional: ' . $e->getMessage()]
            ];
        }
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
        try {
            if ($bulan && $tahun) {
                return BulanTahun::where('bulan', sprintf('%02d', $bulan))
                    ->where('tahun', $tahun)
                    ->first();
            }
            return BulanTahun::where('aktif', 1)->first();
        } catch (\Exception $e) {
            Log::error('Unexpected error in resolveBulanTahun', ['error' => $e->getMessage()]);
            return null;
        }
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
        try {
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
        } catch (\Exception $e) {
            Log::error('Unexpected error in getPreviousMonths', ['error' => $e->getMessage()]);
            return [
                'ids' => [],
                'bulans' => [],
                'tahuns' => []
            ];
        }
    }
}
