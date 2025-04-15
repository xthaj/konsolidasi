<?php

namespace App\Http\Controllers;

use App\Models\BulanTahun;
use App\Models\Inflasi;
use App\Models\Wilayah;
use App\Models\Komoditas;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class VisualisasiController extends Controller
{
    public function create(Request $request): View
    {
        // lock
        $kd_wilayah = '0';

        // Validate kd_wilayah: must be '0' or 2 digits
        if ($kd_wilayah !== '0' && !preg_match('/^\d{2}$/', $kd_wilayah)) {
            Log::warning("Invalid kd_wilayah: {$kd_wilayah}. Defaulting to national (0).");
            $kd_wilayah = '0';
            $request->merge(['kd_wilayah' => '0']);
        }

        $response = [
            'title' => 'Inflasi',
            'message' => '',
            'bulan' => $request->input('bulan', ''),
            'tahun' => $request->input('tahun', ''),
            'kd_wilayah' => $kd_wilayah,
            // lock
            // 'kd_komoditas' => $request->input('kd_komoditas', '000'),
            'kd_komoditas' => '000',
            'errors' => [],
            'charts' => [],
            'chart_status' => [],
            'data' => []
        ];

        Log::info('Request Inputs:', $response);

        // $bulanTahunRecord = $this->resolveBulanTahun($response['bulan'], $response['tahun']);
        // lock
        $bulanTahunRecord = $this->resolveBulanTahun(2, 2025);
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

        // Prepare data based on scope
        $isNational = $kd_wilayah === '0';
        $dataCheck = $isNational
            ? $this->nationalDataLogic($bulanTahunRecord->bulan_tahun_id, $kd_wilayah, $response['kd_komoditas'])
            : $this->provinceDataLogic($bulanTahunRecord->bulan_tahun_id, $kd_wilayah, $response['kd_komoditas']);
        Log::info('DataCheck Result:', $dataCheck);

        $monthNames = (new BulanTahun())->monthNames ?? $this->defaultMonthNames();
        $wilayahName = $isNational
            ? 'Nasional'
            : (Wilayah::where('kd_wilayah', $kd_wilayah)->value('nama_wilayah') ?? 'Unknown');
        $namaKomoditas = Komoditas::where('kd_komoditas', $response['kd_komoditas'])->value('nama_komoditas') ?? 'Unknown';

        $monthName = $monthNames[sprintf('%02d', $response['bulan'])] ?? $response['bulan'];
        $response['title'] = trim("Inflasi Komoditas {$namaKomoditas} {$wilayahName} {$monthName} {$response['tahun']}");
        $response['charts'] = $dataCheck['charts'];
        $response['chart_status'] = $dataCheck['chart_status'];
        $response['data'] = $dataCheck['data'];

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

    private function nationalDataLogic($bulanTahunId, $kd_wilayah, $kd_komoditas): array
    {
        $bulanTahunRecord = BulanTahun::find($bulanTahunId);
        if (!$bulanTahunRecord) {
            return [
                'charts' => [],
                'chart_status' => [],
                'data' => [],
                'errors' => ['Bulan dan tahun tidak ditemukan.']
            ];
        }

        $monthNames = (new BulanTahun())->monthNames ?? $this->defaultMonthNames();
        $levelNames = $this->defaultLevelNames();
        $monthsData = $this->getPreviousMonths($bulanTahunRecord->bulan, $bulanTahunRecord->tahun, 5);

        if (empty($monthsData['ids'])) {
            return [
                'charts' => [],
                'chart_status' => [],
                'data' => [],
                'errors' => ["Data untuk bulan {$monthNames[$bulanTahunRecord->bulan]} tahun {$bulanTahunRecord->tahun} tidak ditemukan."]
            ];
        }

        $errors = [];
        $charts = [];
        $chart_status = [];
        $data = [];

        // Stacked Line and Horizontal Bar
        $stackedLineData = [
            'series' => [],
            'xAxis' => array_map(fn($m) => $monthNames[$m], $monthsData['bulans'])
        ];
        $horizontalBarData = [
            'labels' => array_map(fn($m) => $monthNames[$m], $monthsData['bulans']),
            'datasets' => [],
            'regions' => [$kd_wilayah]
        ];
        $summaryData = [];
        $hasCompleteData = true;

        foreach ($levelNames as $kd => $name) {
            $inflasiSeriesData = [];
            $andilData = [];

            foreach ($monthsData['ids'] as $index => $id) {
                $record = Inflasi::where('bulan_tahun_id', $id)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_level', $kd)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('inflasi', 'andil')
                    ->first();

                $dataExists = $record && !is_null($record->inflasi);
                $inflasi = $dataExists ? $record->inflasi : 0;
                $andil = $dataExists ? $record->andil : 0;

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
                'data' => $inflasiSeriesData,
                'andil' => $andilData
            ];
            $horizontalBarData['datasets'][] = [
                'label' => $name,
                'inflasi' => $inflasiSeriesData,
                'andil' => $andilData,
                'region' => $kd_wilayah,
                'region_name' => 'Nasional'
            ];
            $summaryData[$name] = [
                'inflasi' => end($inflasiSeriesData),
                'andil' => end($andilData)
            ];
        }

        $charts[] = 'stackedLine';
        $chart_status['stackedLine'] = $hasCompleteData ? 'complete' : 'incomplete';
        $charts[] = 'horizontalBar';
        $chart_status['horizontalBar'] = $hasCompleteData ? 'complete' : 'incomplete';

        // National Scope Charts
        // $provinces = Wilayah::whereRaw('LEN(kd_wilayah) = 2')
        $provinces = Wilayah::whereRaw('LENGTH(kd_wilayah) = 2')
            ->pluck('nama_wilayah', 'kd_wilayah')
            ->toArray();
        // $kabkots = Wilayah::whereRaw('LEN(kd_wilayah) = 4')
        $kabkots = Wilayah::whereRaw('LENGTH(kd_wilayah) = 4')
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
        $provinsiChoroplethData = [];
        $kabkotChoroplethData = [];

        foreach ($kdLevels as $kdLevel) {
            $missingProvs = [];
            $missingKabkots = [];
            $provInflasiComplete = true;
            $kabkotInflasiComplete = true;

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

            foreach ($provinces as $provKd => $provName) {
                $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                    ->where('kd_wilayah', $provKd)
                    ->where('kd_level', $kdLevel)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('inflasi')
                    ->first();
                $xIndex = array_search($kdLevel, $kdLevels);
                $yIndex = array_search($provName, array_values($provinces));
                $heatmapData['values'][] = [
                    $xIndex,
                    $yIndex,
                    $record && !is_null($record->inflasi) ? (float)$record->inflasi : null
                ];
            }

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
            $provinsiChoroplethData[] = [
                'kd_level' => $kdLevel,
                'regions' => $provRegions,
                'names' => $provNames,
                'inflasi' => $provInflasi
            ];

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
                $kabkotChoroplethData[] = [
                    'kd_level' => $kdLevel,
                    'regions' => $kabkotRegions,
                    'names' => $kabkotNames,
                    'inflasi' => $kabkotInflasi
                ];
            }
        }

        $charts = array_merge($charts, ['heatmap', 'stackedBar', 'provHorizontalBar', 'kabkotHorizontalBar', 'provinsiChoropleth', 'kabkotChoropleth']);
        $chart_status = array_merge($chart_status, [
            'heatmap' => $provInflasiComplete ? 'complete' : 'incomplete',
            'stackedBar' => $provInflasiComplete ? 'complete' : 'incomplete',
            'provHorizontalBar' => $provInflasiComplete ? 'complete' : 'incomplete',
            'kabkotHorizontalBar' => $kabkotInflasiComplete ? 'complete' : 'incomplete',
            'provinsiChoropleth' => $provInflasiComplete ? 'complete' : 'incomplete',
            'kabkotChoropleth' => $kabkotInflasiComplete ? 'complete' : 'incomplete'
        ]);

        $data['stackedLine'] = $stackedLineData;
        $data['horizontalBar'] = $horizontalBarData;
        $data['summary'] = $summaryData;
        $data['heatmap'] = $heatmapData;
        $data['stackedBar'] = $stackedBarData;
        $data['provHorizontalBar'] = $provHorizontalBarData;
        $data['kabkotHorizontalBar'] = $kabkotHorizontalBarData;
        $data['provinsiChoropleth'] = $provinsiChoroplethData;
        $data['kabkotChoropleth'] = $kabkotChoroplethData;

        return [
            'charts' => array_unique($charts),
            'chart_status' => $chart_status,
            'data' => $data,
            'errors' => $errors
        ];
    }

    private function provinceDataLogic($bulanTahunId, $kd_wilayah, $kd_komoditas): array
    {
        $bulanTahunRecord = BulanTahun::find($bulanTahunId);
        if (!$bulanTahunRecord) {
            return [
                'charts' => [],
                'chart_status' => [],
                'data' => [],
                'errors' => ['Bulan dan tahun tidak ditemukan.']
            ];
        }

        $monthNames = (new BulanTahun())->monthNames ?? $this->defaultMonthNames();
        $levelNames = ['01' => 'Harga Konsumen Kota'];
        $monthsData = $this->getPreviousMonths($bulanTahunRecord->bulan, $bulanTahunRecord->tahun, 5);

        if (empty($monthsData['ids'])) {
            return [
                'charts' => [],
                'chart_status' => [],
                'data' => [],
                'errors' => ["Data untuk bulan {$monthNames[$bulanTahunRecord->bulan]} tahun {$bulanTahunRecord->tahun} tidak ditemukan."]
            ];
        }

        $errors = [];
        $charts = [];
        $chart_status = [];
        $data = [];
        $wilayahName = Wilayah::where('kd_wilayah', $kd_wilayah)->value('nama_wilayah') ?? 'Unknown';

        // Provinsi Stacked Line and Horizontal Bar
        $provinsiStackedLineData = [
            'series' => [],
            'xAxis' => array_map(fn($m) => $monthNames[$m], $monthsData['bulans'])
        ];
        $provinsiHorizontalBarData = [
            'labels' => array_map(fn($m) => $monthNames[$m], $monthsData['bulans']),
            'datasets' => [],
            'regions' => [$kd_wilayah]
        ];
        $summaryData = [];
        $hasCompleteData = true;

        foreach ($levelNames as $kd => $name) {
            $inflasiSeriesData = [];

            foreach ($monthsData['ids'] as $index => $id) {
                $record = Inflasi::where('bulan_tahun_id', $id)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_level', $kd)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('inflasi')
                    ->first();

                $inflasi = $record && !is_null($record->inflasi) ? $record->inflasi : 0;
                $inflasiSeriesData[] = $inflasi;

                if (!$record || is_null($record->inflasi)) {
                    $monthName = $monthNames[$monthsData['bulans'][$index]];
                    $errors[] = "Data bulan {$monthName} level harga {$name} tidak tersedia.";
                    $hasCompleteData = false;
                }
            }

            $provinsiStackedLineData['series'][] = [
                'name' => $name,
                'data' => $inflasiSeriesData
            ];
            $provinsiHorizontalBarData['datasets'][] = [
                'label' => $name,
                'inflasi' => $inflasiSeriesData,
                'region' => $kd_wilayah,
                'region_name' => $wilayahName
            ];
            $summaryData[$name] = [
                'inflasi' => end($inflasiSeriesData)
            ];
        }

        $charts[] = 'provinsiStackedLine';
        $chart_status['provinsiStackedLine'] = $hasCompleteData ? 'complete' : 'incomplete';
        $charts[] = 'provinsiHorizontalBar';
        $chart_status['provinsiHorizontalBar'] = $hasCompleteData ? 'complete' : 'incomplete';

        // Provinsi Kabkot Horizontal Bar and Choropleth
        $kabkots = Wilayah::where('parent_kd', $kd_wilayah)->pluck('nama_wilayah', 'kd_wilayah')->toArray();
        $latestMonthId = $monthsData['ids'][0];
        $provinsiKabkotHorizontalBarData = [];
        $provinsiKabkotChoroplethData = [];
        $hasCompleteKabkotData = true;

        if ($kabkots) {
            $kabkotRegions = array_keys($kabkots);
            $kabkotNames = array_values($kabkots);
            $kabkotInflasi = array_map(
                fn($kabKd) => Inflasi::where('bulan_tahun_id', $latestMonthId)
                    ->where('kd_wilayah', $kabKd)
                    ->where('kd_level', '01')
                    ->where('kd_komoditas', $kd_komoditas)
                    ->value('inflasi') ?? null,
                $kabkotRegions
            );

            $missingKabkots = [];
            foreach ($kabkots as $kabKd => $kabName) {
                $record = Inflasi::where('bulan_tahun_id', $latestMonthId)
                    ->where('kd_wilayah', $kabKd)
                    ->where('kd_level', '01')
                    ->where('kd_komoditas', $kd_komoditas)
                    ->select('inflasi')
                    ->first();

                if (!$record || is_null($record->inflasi)) {
                    $missingKabkots[] = $kabName;
                    $hasCompleteKabkotData = false;
                }
            }
            if (!empty($missingKabkots)) {
                $errors[] = "Data kabupaten/kota " . implode(', ', $missingKabkots) . " tidak tersedia.";
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

            $provinsiKabkotHorizontalBarData[] = [
                'kd_level' => '01',
                'regions' => $kabkotRegions,
                'names' => $kabkotNames,
                'inflasi' => $kabkotInflasi
            ];
            $provinsiKabkotChoroplethData[] = [
                'kd_level' => '01',
                'regions' => $kabkotRegions,
                'names' => $kabkotNames,
                'inflasi' => $kabkotInflasi
            ];
        }

        $charts[] = 'provinsiKabkotHorizontalBar';
        $chart_status['provinsiKabkotHorizontalBar'] = $hasCompleteKabkotData ? 'complete' : 'incomplete';
        $charts[] = 'provinsiKabkotChoropleth';
        $chart_status['provinsiKabkotChoropleth'] = $hasCompleteKabkotData ? 'complete' : 'incomplete';

        // Explicitly mark unavailable charts
        $chart_status['heatmap'] = 'not_applicable';
        $chart_status['stackedBar'] = 'not_applicable';
        $chart_status['provHorizontalBar'] = 'not_applicable';
        $chart_status['provinsiChoropleth'] = 'not_applicable';
        $chart_status['stackedLine'] = 'not_applicable';
        $chart_status['horizontalBar'] = 'not_applicable';
        $chart_status['kabkotHorizontalBar'] = 'not_applicable';
        $chart_status['kabkotChoropleth'] = 'not_applicable';

        $data['provinsiStackedLine'] = $provinsiStackedLineData;
        $data['provinsiHorizontalBar'] = $provinsiHorizontalBarData;
        $data['summary'] = $summaryData;
        $data['provinsiKabkotHorizontalBar'] = $provinsiKabkotHorizontalBarData;
        $data['provinsiKabkotChoropleth'] = $provinsiKabkotChoroplethData;

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
