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

class HargaController extends Controller
{
    public function create(): View
    {
        return view('harga.index');
    }

    public function fetchHargaData(Request $request): JsonResponse
    {
        try {
            $activeBulanTahun = BulanTahun::where('aktif', 1)->first();
            if (!$activeBulanTahun) {
                return response()->json([
                    'message' => 'Beberapa data tidak tersedia: Tidak ada periode aktif.',
                    'data' => [
                        'title' => 'Harga',
                        'errors' => ['Tidak ada periode aktif.'],
                        'chart_status' => [],
                        'chart_data' => []
                    ]
                ], 200);
            }

            $defaults = [
                'bulan' => $activeBulanTahun->bulan,
                'tahun' => $activeBulanTahun->tahun,
                'kd_komoditas' => null
            ];

            try {
                $validated = $request->validate([
                    'bulan' => 'required|integer|between:1,12',
                    'tahun' => 'required|integer|between:2000,2100',
                    'kd_komoditas' => 'nullable|string|max:3',
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'message' => 'Validation failed: ' . implode(', ', array_merge(...array_values($e->errors()))),
                    'data' => ['title' => 'Harga', 'errors' => $e->errors(), 'chart_status' => [], 'chart_data' => []]
                ], 422);
            }

            $bulanTahunRecord = $this->resolveBulanTahun($validated['bulan'] ?? null, $validated['tahun'] ?? null);
            if (!$bulanTahunRecord) {
                return response()->json([
                    'message' => 'Beberapa data tidak tersedia: Belum ada data di periode terpilih.',
                    'data' => [
                        'title' => 'Harga',
                        'errors' => ['Belum ada data di periode terpilih.'],
                        'chart_status' => [],
                        'chart_data' => []
                    ]
                ], 200);
            }

            $bulan = sprintf('%02d', $bulanTahunRecord->bulan);
            $tahun = $bulanTahunRecord->tahun;
            $kd_komoditas = $validated['kd_komoditas'] ?? null;

            if (!$kd_komoditas) {
                $firstHarga = Komoditas::where('is_harga', true)->orderBy('kd_komoditas')->first();
                $kd_komoditas = $firstHarga ? $firstHarga->kd_komoditas : 0;
            }

            $kd_komoditas = (int) $kd_komoditas;
            if ($kd_komoditas < 0 || $kd_komoditas > 255) {
                return response()->json([
                    'message' => 'Beberapa data tidak tersedia: Kode komoditas tidak valid.',
                    'data' => [
                        'title' => 'Harga',
                        'errors' => ['Kode komoditas tidak valid.'],
                        'chart_status' => [],
                        'chart_data' => []
                    ]
                ], 200);
            }

            $namaKomoditas = Komoditas::where('kd_komoditas', $kd_komoditas)->value('nama_komoditas') ?? 'Unknown';
            $monthName = BulanTahun::getBulanName($bulan);

            $response = [
                'title' => trim("Harga Komoditas {$namaKomoditas} Nasional {$monthName} {$tahun}"),
                'errors' => [],
                'chart_status' => [],
                'chart_data' => []
            ];

            $chartData = $this->fetchHargaCharts($bulanTahunRecord->bulan_tahun_id, $kd_komoditas, $namaKomoditas, $monthName, $tahun);

            $response['chart_status'] = $chartData['chart_status'];
            $response['chart_data'] = $chartData['chart_data'];
            $response['errors'] = $chartData['errors'];

            $message = empty($response['errors'])
                ? 'Data berhasil diambil'
                : 'Beberapa data tidak tersedia: ' . implode(', ', $response['errors']);

            return response()->json([
                'message' => $message,
                'data' => $response
            ], 200);
        } catch (\Exception $e) {
            Log::error('Unexpected error in fetchHargaData', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memuat data: ' . $e->getMessage(),
                'data' => ['title' => 'Harga', 'errors' => ['Terjadi kesalahan server.'], 'chart_status' => [], 'chart_data' => []]
            ], 500);
        }
    }

    private function fetchHargaCharts(int $bulanTahunId, string $kd_komoditas, string $namaKomoditas, string $monthName, string $tahun): array
    {
        try {
            $bulanTahun = BulanTahun::find($bulanTahunId);
            if (!$bulanTahun) {
                return [
                    'chart_status' => [],
                    'chart_data' => [],
                    'errors' => ['Bulan dan tahun tidak ditemukan.']
                ];
            }

            $kdLevels = ['01', '02', '03', '04', '05'];
            $kd_wilayah = '0';

            $monthsData = $this->getPreviousMonths($bulanTahun->bulan, $bulanTahun->tahun, 5);
            if (empty($monthsData['ids'])) {
                return [
                    'chart_status' => [],
                    'chart_data' => [],
                    'errors' => ["Data untuk bulan " . BulanTahun::getBulanName($bulanTahun->bulan) . " tahun {$bulanTahun->tahun} tidak ditemukan."]
                ];
            }

            $errors = [];
            $chart_status = [];
            $chart_data = [];

            // --- Line Chart (harga, non-cumulative) ---
            $lineData = ['xAxis' => [], 'series' => []];
            foreach ($monthsData['bulans'] as $m) {
                $lineData['xAxis'][] = BulanTahun::getBulanName($m);
            }

            $hargaRecords = Inflasi::whereIn('bulan_tahun_id', $monthsData['ids'])
                ->where('kd_wilayah', $kd_wilayah)
                ->whereIn('kd_level', $kdLevels)
                ->where('kd_komoditas', $kd_komoditas)
                ->select('bulan_tahun_id', 'kd_level', 'harga', 'rentang_bawah', 'rentang_atas')
                ->get()
                ->keyBy(fn($item) => "{$item->bulan_tahun_id}-{$item->kd_level}");

            $missingLevels = [];
            $hasCompleteLine = true;

            foreach ($kdLevels as $kd) {
                $name = LevelHarga::getLevelHargaNameComplete($kd);
                $hargaData = [];
                $rentangBawahData = [];
                $rentangAtasData = [];
                foreach ($monthsData['ids'] as $index => $id) {
                    $record = $hargaRecords["{$id}-{$kd}"] ?? null;
                    $harga = $record && !is_null($record->harga) ? number_format($record->harga, 2, '.', '') : null;
                    $hargaData[] = $harga;
                    $rentangBawahData[] = $record && !is_null($record->rentang_bawah) ? number_format($record->rentang_bawah, 2, '.', '') : null;
                    $rentangAtasData[] = $record && !is_null($record->rentang_atas) ? number_format($record->rentang_atas, 2, '.', '') : null;

                    if (is_null($harga)) {
                        $missingLevels[$id][$kd] = [
                            'name' => $name,
                            'month' => $monthsData['bulans'][$index],
                            'year' => $monthsData['tahuns'][$index]
                        ];
                        $hasCompleteLine = false;
                    }
                }
                $lineData['series'][] = [
                    'name' => $name,
                    'harga' => $hargaData,
                    'rentang_bawah' => $rentangBawahData,
                    'rentang_atas' => $rentangAtasData,
                ];
            }

            $chart_data['line'] = $lineData;
            $chart_status['line'] = [
                'title' => "Tren Harga {$namaKomoditas} {$monthName} {$tahun}",
                'status' => $hasCompleteLine ? 'complete' : 'incomplete',
            ];

            // --- Summary / Info Cards ---
            $summaryData = [];
            $latestHargaRecords = Inflasi::where('bulan_tahun_id', $bulanTahunId)
                ->where('kd_wilayah', $kd_wilayah)
                ->whereIn('kd_level', $kdLevels)
                ->where('kd_komoditas', $kd_komoditas)
                ->select('kd_level', 'harga', 'rentang_bawah', 'rentang_atas')
                ->get()
                ->keyBy('kd_level');

            foreach ($kdLevels as $kd) {
                $name = LevelHarga::getLevelHargaNameComplete($kd);
                $record = $latestHargaRecords[$kd] ?? null;
                $summaryData[$name] = [
                    'harga' => $record && !is_null($record->harga) ? number_format($record->harga, 2, '.', '') : null,
                    'rentang_bawah' => $record && !is_null($record->rentang_bawah) ? number_format($record->rentang_bawah, 2, '.', '') : null,
                    'rentang_atas' => $record && !is_null($record->rentang_atas) ? number_format($record->rentang_atas, 2, '.', '') : null,
                ];
            }

            $chart_data['summary'] = $summaryData;

            // --- HK Provincial Horizontal Bar (level 01 only) ---
            $provinces = Wilayah::where('flag', 2)
                ->pluck('nama_wilayah', 'kd_wilayah')
                ->toArray();

            $provHargaRecords = Inflasi::where('bulan_tahun_id', $bulanTahunId)
                ->whereIn('kd_wilayah', array_keys($provinces))
                ->where('kd_level', '01')
                ->where('kd_komoditas', $kd_komoditas)
                ->select('kd_wilayah', 'harga', 'rentang_bawah', 'rentang_atas')
                ->get()
                ->keyBy('kd_wilayah');

            $provRegions = array_keys($provinces);
            $provNames = array_values($provinces);
            $provHarga = [];
            $provBawah = [];
            $provAtas = [];
            $hasCompleteProv = true;

            foreach ($provinces as $provKd => $provName) {
                $record = $provHargaRecords[$provKd] ?? null;
                $harga = $record && !is_null($record->harga) ? number_format($record->harga, 2, '.', '') : null;
                $provHarga[] = $harga;
                $provBawah[] = $record && !is_null($record->rentang_bawah) ? number_format($record->rentang_bawah, 2, '.', '') : null;
                $provAtas[] = $record && !is_null($record->rentang_atas) ? number_format($record->rentang_atas, 2, '.', '') : null;
                if (is_null($harga)) {
                    $hasCompleteProv = false;
                }
            }

            array_multisort(
                $provHarga,
                SORT_ASC,
                SORT_NUMERIC,
                array_map(fn($val) => $val === null ? PHP_INT_MAX : 0, $provHarga),
                SORT_ASC,
                $provRegions,
                SORT_DESC,
                $provNames,
                $provBawah,
                $provAtas
            );

            $chart_data['provHorizontalBar'] = [[
                'kd_level' => '01',
                'regions' => $provRegions,
                'names' => $provNames,
                'harga' => $provHarga,
                'rentang_bawah' => $provBawah,
                'rentang_atas' => $provAtas,
            ]];
            $chart_status['provHorizontalBar'] = [
                'title' => "Harga per Provinsi {$namaKomoditas} {$monthName} {$tahun}",
                'status' => $hasCompleteProv ? 'complete' : 'incomplete',
            ];

            // --- Kabkot Horizontal Bar (HK only, level 01) ---
            $kabkots = Wilayah::where('flag', 3)
                ->where('inflasi_tracked', 1)
                ->pluck('nama_wilayah', 'kd_wilayah')
                ->toArray();

            $kabkotHargaRecords = Inflasi::where('bulan_tahun_id', $bulanTahunId)
                ->whereIn('kd_wilayah', array_keys($kabkots))
                ->where('kd_level', '01')
                ->where('kd_komoditas', $kd_komoditas)
                ->select('kd_wilayah', 'harga', 'rentang_bawah', 'rentang_atas')
                ->get()
                ->keyBy('kd_wilayah');

            $kabkotRegions = array_keys($kabkots);
            $kabkotNames = array_values($kabkots);
            $kabkotHarga = [];
            $kabkotBawah = [];
            $kabkotAtas = [];
            $hasCompleteKabkot = true;

            foreach ($kabkots as $kabKd => $kabName) {
                $record = $kabkotHargaRecords[$kabKd] ?? null;
                $harga = $record && !is_null($record->harga) ? number_format($record->harga, 2, '.', '') : null;
                $kabkotHarga[] = $harga;
                $kabkotBawah[] = $record && !is_null($record->rentang_bawah) ? number_format($record->rentang_bawah, 2, '.', '') : null;
                $kabkotAtas[] = $record && !is_null($record->rentang_atas) ? number_format($record->rentang_atas, 2, '.', '') : null;
                if (is_null($harga)) {
                    $hasCompleteKabkot = false;
                }
            }

            array_multisort(
                $kabkotHarga,
                SORT_ASC,
                SORT_NUMERIC,
                array_map(fn($val) => $val === null ? PHP_INT_MAX : 0, $kabkotHarga),
                SORT_ASC,
                $kabkotRegions,
                SORT_DESC,
                $kabkotNames,
                $kabkotBawah,
                $kabkotAtas
            );

            $chart_data['kabkotHorizontalBar'] = [[
                'kd_level' => '01',
                'regions' => $kabkotRegions,
                'names' => $kabkotNames,
                'harga' => $kabkotHarga,
                'rentang_bawah' => $kabkotBawah,
                'rentang_atas' => $kabkotAtas,
            ]];
            $chart_status['kabkotHorizontalBar'] = [
                'title' => "Harga per Kabupaten/Kota {$namaKomoditas} {$monthName} {$tahun}",
                'status' => $hasCompleteKabkot ? 'complete' : 'incomplete',
            ];

            // Error messages for missing data — consolidated to avoid verbosity
            $missingByLevel = [];
            foreach ($missingLevels as $id => $levels) {
                foreach ($levels as $data) {
                    $key = $data['name'];
                    $missingByLevel[$key] = ($missingByLevel[$key] ?? 0) + 1;
                }
            }
            foreach ($missingByLevel as $levelName => $count) {
                $errors[] = "Data harga untuk level {$levelName} tidak lengkap ({$count} dari 5 bulan terakhir tidak tersedia).";
            }

            return [
                'chart_status' => $chart_status,
                'chart_data' => $chart_data,
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            Log::error('Unexpected error in fetchHargaCharts', ['error' => $e->getMessage()]);
            return [
                'chart_status' => [],
                'chart_data' => [],
                'errors' => ['Gagal memuat data harga: ' . $e->getMessage()]
            ];
        }
    }

    private function resolveBulanTahun(?string $bulan, ?string $tahun): ?BulanTahun
    {
        try {
            if ($bulan && $tahun) {
                return BulanTahun::where('bulan', sprintf('%02d', $bulan))
                    ->where('tahun', $tahun)
                    ->orderBy('bulan_tahun_id', 'asc')
                    ->first();
            }
            return BulanTahun::where('aktif', 1)->first();
        } catch (\Exception $e) {
            Log::error('Unexpected error in resolveBulanTahun', ['error' => $e->getMessage()]);
            return null;
        }
    }

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
                    ->orderBy('bulan_tahun_id', 'asc')
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
            return ['ids' => [], 'bulans' => [], 'tahuns' => []];
        }
    }
}
