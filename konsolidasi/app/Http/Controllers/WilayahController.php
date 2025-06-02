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

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'nama_wilayah' => 'required|string|max:255',
    //     ]);

    //     try {
    //         $wilayah = Wilayah::create([
    //             'kd_wilayah' => $this->generateKodeWilayah(),
    //             'nama_wilayah' => $request->nama_wilayah,
    //             'flag' => $request->flag ?? 2, // Default to 2 if not provided
    //         ]);

    //         Cache::forget('wilayah_data'); // Clear cache to refresh data
    //         return response()->json(['message' => 'Wilayah berhasil ditambahkan', 'data' => new WilayahResource($wilayah)], 201);
    //     } catch (\Exception $e) {
    //         Log::error('Failed to add wilayah: ' . $e->getMessage());
    //         return response()->json(['message' => 'Gagal menambahkan wilayah', 'error' => $e->getMessage()], 500);
    //     }
    // }

    // public function update(Request $request, $kd_wilayah)
    // {
    //     $request->validate([
    //         'nama_wilayah' => 'required|string|max:255',
    //     ]);

    //     try {
    //         $wilayah = Wilayah::where('kd_wilayah', $kd_wilayah)->firstOrFail();
    //         $wilayah->update([
    //             'nama_wilayah' => $request->nama_wilayah,
    //         ]);

    //         Cache::forget('wilayah_data'); // Clear cache to refresh data
    //         return response()->json(['message' => 'Wilayah berhasil diperbarui', 'data' => new WilayahResource($wilayah)]);
    //     } catch (\Exception $e) {
    //         Log::error('Failed to update wilayah: ' . $e->getMessage());
    //         return response()->json(['message' => 'Gagal memperbarui wilayah', 'error' => $e->getMessage()], 500);
    //     }
    // }

    // public function destroy($kd_wilayah)
    // {
    //     try {
    //         $wilayah = Wilayah::where('kd_wilayah', $kd_wilayah)->firstOrFail();
    //         $wilayah->delete();

    //         Cache::forget('wilayah_data'); // Clear cache to refresh data
    //         return response()->json(['message' => 'Wilayah berhasil dihapus']);
    //     } catch (\Exception $e) {
    //         Log::error('Failed to delete wilayah: ' . $e->getMessage());
    //         return response()->json(['message' => 'Gagal menghapus wilayah', 'error' => $e->getMessage()], 500);
    //     }
    // }

    // private function generateKodeWilayah()
    // {
    //     // Simple logic to generate unique kode_wilayah (adjust as needed)
    //     $lastWilayah = Wilayah::orderBy('kd_wilayah', 'desc')->first();
    //     $lastCode = $lastWilayah ? (int) $lastWilayah->kd_wilayah : 0;
    //     return str_pad($lastCode + 1, 4, '0', STR_PAD_LEFT);
    // }

    /**
     * Fetch all wilayah with caching.
     */
    public function getAllWilayah(): JsonResponse
    {
        try {
            $data = Cache::remember('all_wilayah_data', now()->addHours(24), function () {
                Log::info('Wilayah data fetched from database for all_wilayah_data', ['timestamp' => now()]);
                return Wilayah::orderBy('nama_wilayah', 'asc')->get();
            });

            // add here: Handle empty result
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
            $data = Cache::remember('wilayah_data', now()->addHours(24), function () {
                Log::info('Wilayah data fetched from database for wilayah_data', ['timestamp' => now()]);
                return [
                    'provinces' => Wilayah::where('flag', 2)->orderBy('nama_wilayah', 'asc')->get(),
                    'kabkots' => Wilayah::where('flag', 3)->orderBy('nama_wilayah', 'asc')->get(),
                ];
            });

            // add here: Handle empty result
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
