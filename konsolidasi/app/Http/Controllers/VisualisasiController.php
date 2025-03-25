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
        ];

        // Log initial request inputs for debugging
        Log::info('Request Inputs:', $response);

        // Determine BulanTahun
        $bulanTahunRecord = $this->resolveBulanTahun($response['bulan'], $response['tahun']);
        if (!$bulanTahunRecord) {
            $response['message'] = 'Bulan dan tahun tidak ditemukan.';
            Log::warning('BulanTahun not found for bulan: ' . $response['bulan'] . ', tahun: ' . $response['tahun']);
            return view('visualisasi.harmonisasi', $response);
        }

        // Update response with resolved values
        $response['bulan'] = $bulanTahunRecord->bulan;
        $response['tahun'] = $bulanTahunRecord->tahun;
        $request->merge(['bulan' => $bulanTahunRecord->bulan, 'tahun' => $bulanTahunRecord->tahun]);
        Log::info('Resolved BulanTahun:', ['bulan' => $response['bulan'], 'tahun' => $response['tahun'], 'bulan_tahun_id' => $bulanTahunRecord->bulan_tahun_id]);

        // Check data and prepare visualization
        $dataCheck = $this->cekDataLogic(
            $bulanTahunRecord->bulan_tahun_id,
            $response['kd_wilayah'],
            $response['kd_komoditas']
        );
        Log::info('DataCheck Result:', $dataCheck);

        // Set title and data
        $response['title'] = $this->generatePageTitle($request);
        $response['data'] = $dataCheck['data'];

        // Handle errors
        if (!empty($dataCheck['errors'])) {
            $response['message'] = 'Beberapa data hilang: ' . implode(', ', $dataCheck['errors']);
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
        $bulanTahunRecord = BulanTahun::find($bulanTahunId);
        if (!$bulanTahunRecord) {
            return [
                'charts' => [],
                'data' => [],
                'errors' => ['Bulan dan tahun tidak ditemukan.'],
            ];
        }

        $monthNames = (new BulanTahun())->monthNames ?? $this->defaultMonthNames();
        $levelNames = $this->defaultLevelNames();

        $monthsData = $this->getPreviousMonths($bulanTahunRecord->bulan, $bulanTahunRecord->tahun, 5);
        if (empty($monthsData['ids'])) {
            return [
                'charts' => [],
                'data' => [],
                'errors' => ["Data untuk bulan {$bulanTahunRecord->bulan} tahun {$bulanTahunRecord->tahun} tidak ditemukan."],
            ];
        }

        $errors = [];
        $kabkotMissingCount = 0; // Counter for missing kabkot data
        $missingKabkots = []; // Array to store names of missing kabkots
        $stackedLineData = ['series' => [], 'xAxis' => array_map(fn($m) => $monthNames[$m], $monthsData['bulans'])];
        $horizontalBarData = ['labels' => array_map(fn($m) => $monthNames[$m], $monthsData['bulans']), 'datasets' => []];

        // Fetch all provinces and kabkots
        $provinces = Wilayah::whereRaw('LEN(kd_wilayah) = 2')->get()->pluck('nama_wilayah', 'kd_wilayah')->toArray();
        $kabkots = Wilayah::whereRaw('LEN(kd_wilayah) = 4')->get()->pluck('nama_wilayah', 'kd_wilayah')->toArray();
        $heatmapData = ['provinces' => [], 'values' => []];

        // Original charts for the selected kd_wilayah
        foreach ($levelNames as $kd => $name) {
            $inflasiSeriesData = [];
            $andilData = [];
            $levelComplete = true;

            foreach ($monthsData['ids'] as $index => $id) {
                $record = Inflasi::where('bulan_tahun_id', $id)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_level', $kd)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('inflasi', 'andil')
                    ->first();

                $dataExists = $record && !is_null($record->inflasi) && !is_null($record->andil);
                $inflasi = $dataExists ? $record->inflasi : 0;
                $andil = $dataExists ? $record->andil : 0;

                $inflasiSeriesData[] = $inflasi;
                $andilData[] = $andil;

                if (!$dataExists) {
                    $monthName = $monthNames[$monthsData['bulans'][$index]];
                    $errors[] = "Bulan {$monthName} tahun {$monthsData['tahuns'][$index]}, Level {$name}: Data inflasi atau andil hilang.";
                    $levelComplete = false;
                }
            }

            $stackedLineData['series'][] = ['name' => $name, 'data' => $inflasiSeriesData];
            $horizontalBarData['datasets'][] = [
                'label' => $name,
                'inflasi' => $inflasiSeriesData,
                'andil' => $andilData,
            ];
        }

        // Define kd_levels for heatmap x-axis
        $kdLevels = ['01', '02', '03', '04', '05'];
        $latestMonthId = $monthsData['ids'][0]; // Use the latest month from monthsData

        // Heatmap Data
        $heatmapData = [
            'xAxis' => $kdLevels,
            'yAxis' => [],
            'values' => []
        ];

        foreach ($provinces as $provKd => $provName) {
            $heatmapData['yAxis'][] = $provName;
            foreach ($kdLevels as $kdLevel) {
                $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                    ->where('kd_wilayah', $provKd)
                    ->where('kd_level', $kdLevel)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('inflasi')
                    ->first();

                $xIndex = array_search($kdLevel, $kdLevels);
                $yIndex = array_search($provName, $heatmapData['yAxis']);

                if ($record && !is_null($record->inflasi)) {
                    $heatmapData['values'][] = [$xIndex, $yIndex, is_numeric($record->inflasi) ? (float)$record->inflasi : null];
                } else {
                    $heatmapData['values'][] = [$xIndex, $yIndex, null];
                    $errors[] = "Provinsi {$provName}, Level {$kdLevel}: Data inflasi tidak tersedia.";
                }
            }
        }

        // Bar Chart Data
        $barChartData = [];
        foreach ($kdLevels as $index => $kdLevel) {
            $barChart = [
                'name' => $levelNames[$kdLevel] ?? "Level $kdLevel",
                'provinces' => [],
                'values' => [],
            ];

            foreach ($provinces as $provKd => $provName) {
                $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                    ->where('kd_wilayah', $provKd)
                    ->where('kd_level', $kdLevel)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('inflasi')
                    ->first();

                if ($record && !is_null($record->inflasi)) {
                    $barChart['provinces'][] = $provName;
                    $barChart['values'][] = (float)$record->inflasi;
                }
            }
            $barChartData[] = $barChart;
        }

        // Stacked Bar Chart Data
        $stackedBarData = [
            'labels' => $kdLevels,
            'datasets' => [
                ['label' => 'Menurun (< 0)', 'stack' => 'inflation', 'data' => [], 'backgroundColor' => '#FF6B6B'],
                ['label' => 'Stable (= 0)', 'stack' => 'inflation', 'data' => [], 'backgroundColor' => '#FFD93D'],
                ['label' => 'Naik (> 0)', 'stack' => 'inflation', 'data' => [], 'backgroundColor' => '#6BCB77'],
                ['label' => 'Not Available', 'stack' => 'inflation', 'data' => [], 'backgroundColor' => '#D3D3D3'],
            ],
        ];

        foreach ($kdLevels as $kdLevel) {
            $menurunCount = 0;
            $stableCount = 0;
            $naikCount = 0;
            $notAvailableCount = 0;

            foreach ($provinces as $provKd => $provName) {
                $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                    ->where('kd_wilayah', $provKd)
                    ->where('kd_level', $kdLevel)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('inflasi')
                    ->first();

                $inflasi = $record ? $record->inflasi : null;
                if ($inflasi === null) {
                    $notAvailableCount++;
                } elseif ((float)$inflasi < 0) {
                    $menurunCount++;
                } elseif ((float)$inflasi == 0) {
                    $stableCount++;
                } elseif ((float)$inflasi > 0) {
                    $naikCount++;
                }
            }

            $stackedBarData['datasets'][0]['data'][] = $menurunCount;
            $stackedBarData['datasets'][1]['data'][] = $stableCount;
            $stackedBarData['datasets'][2]['data'][] = $naikCount;
            $stackedBarData['datasets'][3]['data'][] = $notAvailableCount;
        }

        // Provinces and Kabkots with code and name
        $provincesWithKey = Wilayah::whereRaw('LEN(kd_wilayah) = 2')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->kd_wilayah => ['name' => $item->nama_wilayah, 'code' => $item->kd_wilayah]];
            })->toArray();

        $kabkotsWithKey = Wilayah::whereRaw('LEN(kd_wilayah) = 4')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->kd_wilayah => ['name' => $item->nama_wilayah, 'code' => $item->kd_wilayah]];
            })->toArray();

        // Horizontal Bar Chart 1: Provinces
        $provHorizontalBarData = [];
        foreach ($kdLevels as $kdLevel) {
            $dataset = [
                'kd_level' => $kdLevel,
                'regions' => [],
                'names' => [],
                'inflasi' => []
            ];
            foreach ($provincesWithKey as $provKd => $provData) {
                $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                    ->where('kd_wilayah', $provKd)
                    ->where('kd_level', $kdLevel)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('inflasi')
                    ->first();

                $dataset['regions'][] = $provData['code'];
                $dataset['names'][] = $provData['name'];
                $dataset['inflasi'][] = $record && !is_null($record->inflasi) ? (float)$record->inflasi : null;
            }
            $provHorizontalBarData[] = $dataset;
        }

        // Horizontal Bar Chart 2: Kabupaten/Kota with error tracking
        $kabkotHorizontalBarData = [];
        foreach ($kdLevels as $kdLevel) {
            $dataset = [
                'kd_level' => $kdLevel,
                'regions' => [],
                'names' => [],
                'inflasi' => []
            ];
            foreach ($kabkotsWithKey as $kabKd => $kabData) {
                $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                    ->where('kd_wilayah', $kabKd)
                    ->where('kd_level', $kdLevel)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('inflasi')
                    ->first();

                $dataset['regions'][] = $kabData['code'];
                $dataset['names'][] = $kabData['name'];
                $inflasiValue = $record && !is_null($record->inflasi) ? (float)$record->inflasi : null;
                $dataset['inflasi'][] = $inflasiValue;

                // Track missing kabkots
                if ($inflasiValue === null) {
                    $kabkotMissingCount++;
                    $missingKabkots[] = $kabData['name'];
                }
            }
            $kabkotHorizontalBarData[] = $dataset;
        }

        // Add kabkot error message based on count
        if ($kabkotMissingCount > 0) {
            $totalKabkots = count($kabkotsWithKey);
            // Convert bulan to a zero-padded two-digit string
            $monthKey = sprintf('%02d', $bulanTahunRecord->bulan);
            $monthName = $monthNames[$monthKey];
            if ($kabkotMissingCount < 30) {
                $missingList = implode(', ', $missingKabkots);
                $errors[] = "Data inflasi tidak tersedia untuk Kabupaten/Kota berikut pada bulan {$monthName} {$bulanTahunRecord->tahun}: {$missingList}.";
            } else {
                $errors[] = "Data inflasi untuk {$kabkotMissingCount} dari {$totalKabkots} Kabupaten/Kota tidak tersedia pada bulan {$monthName} {$bulanTahunRecord->tahun}.";
            }
        }

        // Define available charts
        $charts = [];
        if (!empty($stackedLineData['series'])) $charts[] = 'stackedLine';
        if (!empty($horizontalBarData['datasets'])) $charts[] = 'horizontalBar';
        if (!empty($heatmapData['values'])) $charts[] = 'heatmap';
        if (!empty($barChartData)) $charts[] = 'barCharts';
        if (!empty($stackedBarData['datasets'])) $charts[] = 'stackedBar';
        if (!empty($provHorizontalBarData)) $charts[] = 'provHorizontalBar';
        if (!empty($kabkotHorizontalBarData)) $charts[] = 'kabkotHorizontalBar';

        // Prepare data array
        $data = [
            'stackedLine' => $stackedLineData,
            'horizontalBar' => $horizontalBarData,
            'heatmap' => $heatmapData,
            'barCharts' => $barChartData,
            'stackedBar' => $stackedBarData,
            'provHorizontalBar' => $provHorizontalBarData,
            'kabkotHorizontalBar' => $kabkotHorizontalBarData,
        ];

        // Log final data (optional, remove if not needed)
        Log::info('Final Data Before Return:', [
            'charts' => $charts,
            'data_summary' => array_keys($data),
            'errors' => $errors
        ]);

        // Return the result
        return [
            'charts' => array_unique($charts),
            'data' => $data,
            'errors' => $errors,
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
        $levelHargaMap = $this->defaultLevelNames();

        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $kd_level = $request->input('kd_level', 'all');
        $kd_wilayah = $request->kd_wilayah;

        $wilayah = $kd_wilayah === '0' ? 'Nasional' : (Wilayah::find($kd_wilayah)->nama_wilayah ?? 'Unknown');
        $levelHarga = $levelHargaMap[$kd_level] ?? '';
        $monthName = $monthNames[$bulan] ?? '';

        return trim("Inflasi {$wilayah} {$levelHarga} {$monthName} {$tahun}");
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
