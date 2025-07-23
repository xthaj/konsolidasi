<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use App\Http\Resources\WilayahResource;
use App\Http\Resources\AllWilayahResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;


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
                'data' => AllWilayahResource::collection($data),
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

    public function getSegmentedWilayah(): JsonResponse
    {
        return $this->getSegmentedWilayahData(false);
    }

    public function getInflasiSegmentedWilayah(): JsonResponse
    {
        return $this->getSegmentedWilayahData(true);
    }

    private function getSegmentedWilayahData(bool $inflasiOnly): JsonResponse
    {
        try {
            $cacheKey = $inflasiOnly ? 'wilayah_data_inflasi' : 'wilayah_data';

            $data = Cache::rememberForever($cacheKey, function () use ($inflasiOnly) {
                Log::info('Wilayah data fetched from database for ' . ($inflasiOnly ? 'wilayah_data_inflasi' : 'wilayah_data'), ['timestamp' => now()]);

                Cache::forever('all_wilayah_data', Wilayah::orderBy('kd_wilayah', 'asc')->get());
                Log::info('Wilayah data also cached for all_wilayah_data inside getSegmentedWilayah', ['timestamp' => now()]);

                $kabkotQuery = Wilayah::where('flag', 3);
                if ($inflasiOnly) {
                    $kabkotQuery->where('inflasi_tracked', 1);
                }

                return [
                    'provinces' => Wilayah::where('flag', 2)->orderBy('kd_wilayah', 'asc')->get(),
                    'kabkots'   => $kabkotQuery->orderBy('kd_wilayah', 'asc')->get(),
                ];
            });

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
                    'kabkots'   => WilayahResource::collection($data['kabkots']),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in getSegmentedWilayahData', [
                'message' => $e->getMessage(),
                'stack'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Gagal mengambil data wilayah: ' . $e->getMessage(),
                'data' => ['provinces' => [], 'kabkots' => []],
            ], 500);
        }
    }

    /**
     * Update an existing wilayah.
     *
     * @param Request $request
     * @param string $kd_wilayah
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $kd_wilayah): JsonResponse
    {
        Log::info('Starting updateWilayah', [
            'kd_wilayah' => $kd_wilayah,
            'request_data' => $request->all(),
            'timestamp' => now(),
        ]);

        try {
            $wilayah = Wilayah::find($kd_wilayah);
            if (!$wilayah) {
                Log::warning('Wilayah not found', ['kd_wilayah' => $kd_wilayah]);
                return response()->json([
                    'message' => 'Wilayah tidak ditemukan.',
                    'data' => null,
                ], 404);
            }

            $validated = $request->validate([
                'nama_wilayah' => [
                    'required',
                    'string',
                    'max:255',
                ],
            ], [
                'nama_wilayah.required' => 'Nama wilayah wajib diisi.',
                'nama_wilayah.string' => 'Nama wilayah harus berupa teks.',
                'nama_wilayah.max' => 'Nama wilayah tidak boleh melebihi 255 karakter.',
            ]);

            // Check if there's actually a change in nama_wilayah
            if ($wilayah->nama_wilayah === $validated['nama_wilayah']) {
                Log::info('No changes detected for wilayah', ['kd_wilayah' => $kd_wilayah]);
                return response()->json([
                    'message' => 'Tidak ada perubahan pada data wilayah.',
                ], 200);
            }

            // Update the wilayah record
            $wilayah->update([
                'nama_wilayah' => $validated['nama_wilayah'],
            ]);

            // Clear relevant caches after update
            $this->clearWilayahCache();

            Log::info('Wilayah updated successfully', ['wilayah' => $wilayah->toArray()]);

            return response()->json([
                'message' => 'Wilayah berhasil diperbarui.',
            ], 200);
        } catch (ValidationException $e) {
            Log::warning('Validation failed in updateWilayah', ['errors' => $e->errors()]);
            $errorMessage = 'Validasi gagal: ' . implode(', ', array_merge(...array_values($e->errors())));
            return response()->json([
                'message' => $errorMessage,
                'data' => null,
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in updateWilayah', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Gagal memperbarui wilayah: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    // public function updateTrackedInflasi(Request $request, $kd_wilayah)
    // {
    //     try {
    //         $validated = $request->validate([
    //             'inflasi_tracked' => 'required|boolean',
    //         ]);

    //         $wilayah = Wilayah::findOrFail($kd_wilayah);

    //         if ($wilayah->inflasi_tracked === $validated['inflasi_tracked']) {
    //             return response()->json([
    //                 'message' => 'Tidak ada perubahan pada status pembahasan.',
    //                 'data' => null
    //             ], 200);
    //         }

    //         $wilayah->inflasi_tracked = $validated['inflasi_tracked'];
    //         $wilayah->save();

    //         Log::info('inflasi_tracked updated', [
    //             'kd_wilayah' => $kd_wilayah,
    //         ]);

    //         return response()->json([
    //             'message' => 'Status wilayah inflasi berhasil diperbarui.',
    //             'data' => null
    //         ], 200);
    //     } catch (ValidationException $e) {
    //         Log::warning('Validation failed in updateTrackedInflasi', ['errors' => $e->errors()]);
    //         return response()->json([
    //             'message' => 'Validation failed: ' . implode(', ', array_merge(...array_values($e->errors()))),
    //             'data' => null
    //         ], 422);
    //     } catch (ModelNotFoundException $e) {
    //         Log::warning('Wilayah not found in updateTrackedInflasi', ['kd_wilayah' => $kd_wilayah]);
    //         return response()->json([
    //             'message' => 'Wilayah tidak ditemukan.',
    //             'data' => null
    //         ], 404);
    //     } catch (\Exception $e) {
    //         Log::error('Error in updateTrackedInflasi', ['message' => $e->getMessage()]);
    //         return response()->json([
    //             'message' => 'Gagal memperbarui wilayah inflasi: ' . $e->getMessage(),
    //             'data' => null
    //         ], 500);
    //     }
    // }


    /**
     * Clear the wilayah cache.
     */
    private function clearWilayahCache(): void
    {
        Cache::forget('all_wilayah_data');
        Cache::forget('wilayah_data');
        Cache::forget('wilayah_data_inflasi');
        Log::info('Cache cleared for wilayah_data, all_wilayah_data, and wilayah_data_inflasi', ['timestamp' => now()]);
    }
}
