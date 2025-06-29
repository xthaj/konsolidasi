<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use App\Http\Resources\WilayahResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WilayahController extends Controller
{
    public function index()
    {
        return view('master.wilayah');
    }

    /**
     * Fetch all wilayah with caching.
     */
    public function getAllWilayah(): JsonResponse
    {
        try {
            $data = Cache::rememberForever('all_wilayah_data', function () {
                Log::info('Wilayah data fetched from database for all_wilayah_data', ['timestamp' => now()]);
                return Wilayah::orderBy('kd_wilayah', 'asc')->get();
            });

            // Handle empty result
            if ($data->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada data wilayah tersedia.',
                    'data' => [],
                ], 200);
            }

            return response()->json([
                'message' => 'Data wilayah berhasil diambil.',
                'data' => WilayahResource::collection($data),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in getAllWilayah', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Gagal mengambil data wilayah: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Fetch segmented wilayah (provinces and kabkots) with caching.
     */
    public function getSegmentedWilayah(): JsonResponse
    {
        try {
            $data = Cache::rememberForever('wilayah_data', function () {
                Log::info('Wilayah data fetched from database for wilayah_data', ['timestamp' => now()]);

                Cache::forever('all_wilayah_data', Wilayah::orderBy('kd_wilayah', 'asc')->get());
                Log::info('Wilayah data also cached for all_wilayah_data inside getSegmentedWilayah', ['timestamp' => now()]);

                return [
                    'provinces' => Wilayah::where('flag', 2)->orderBy('nama_wilayah', 'asc')->get(),
                    'kabkots' => Wilayah::where('flag', 3)->orderBy('nama_wilayah', 'asc')->get(),
                ];
            });

            // Handle empty result
            if ($data['provinces']->isEmpty() && $data['kabkots']->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada data wilayah tersedia.',
                    'data' => ['provinces' => [], 'kabkots' => []],
                ], 200);
            }

            return response()->json([
                'message' => 'Data wilayah berhasil diambil.',
                'data' => [
                    'provinces' => WilayahResource::collection($data['provinces']),
                    'kabkots' => WilayahResource::collection($data['kabkots']),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in getSegmentedWilayah', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Gagal mengambil data wilayah: ' . $e->getMessage(),
                'data' => ['provinces' => [], 'kabkots' => []],
            ], 500);
        }
    }

    /**
     * Clear the wilayah cache.
     */
    // private function clearWilayahCache(): void
    // {
    //     Cache::forget('all_wilayah_data');
    //     Cache::forget('wilayah_data');
    //     Log::info('Cache cleared for wilayah_data and all_wilayah_data', ['timestamp' => now()]);
    // }
}
