<?php

namespace App\Http\Controllers;

use App\Http\Resources\BulanTahunResource;
use App\Models\BulanTahun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class BulanTahunController extends Controller
{
    public function pengaturan()
    {
        return view('pengaturan.index');
    }

    /**
     * Update the active BulanTahun period.
     */
    public function update(Request $request): JsonResponse
    {
        Log::info('Attempting to update BulanTahun', [
            'request_data' => $request->all(),
            'timestamp' => now(),
        ]);

        try {
            $validated = $request->validate([
                'bulan' => 'required|integer|between:1,12',
                'tahun' => 'required|integer|digits:4|min:2000|max:2100',
            ], [
                'bulan.between' => 'Bulan harus antara 1 dan 12.',
                'tahun.digits' => 'Tahun harus terdiri dari 4 digit.',
                'tahun.min' => 'Tahun tidak boleh kurang dari 2000.',
                'tahun.max' => 'Tahun tidak boleh lebih dari 2100.',
            ]);

            $bulan = $validated['bulan'];
            $tahun = $validated['tahun'];

            // add here: Check for existing record
            $existing = BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->first();
            if ($existing && $existing->aktif == 1) {
                Log::warning('Attempted to activate already active period', [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'timestamp' => now(),
                ]);
                return response()->json([
                    'message' => 'Bulan dan tahun ini sudah aktif.',
                    'data' => null,
                ], 422);
            }

            return DB::transaction(function () use ($bulan, $tahun, $existing) {
                // Deactivate current active record
                BulanTahun::where('aktif', 1)->update(['aktif' => 0]);
                Log::info('Deactivated current active BulanTahun', ['timestamp' => now()]);

                // Update or create record
                if ($existing) {
                    $existing->update(['aktif' => 1]);
                    Log::info('Activated existing BulanTahun', [
                        'record' => $existing->toArray(),
                        'timestamp' => now(),
                    ]);
                    $updatedRecord = $existing;
                } else {
                    $updatedRecord = BulanTahun::create([
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'aktif' => 1,
                    ]);
                    Log::info('Created new active BulanTahun', [
                        'record' => $updatedRecord->toArray(),
                        'timestamp' => now(),
                    ]);
                }

                // Clear cache
                $this->clearBulanTahunCache();

                return response()->json([
                    'message' => 'Bulan dan tahun berhasil diperbarui.',
                    'data' => null,
                ], 200);
            });
        } catch (ValidationException $e) {
            Log::warning('Validation failed in updateBulanTahun', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'timestamp' => now(),
            ]);
            return response()->json([
                'message' => 'Validasi gagal: ' . implode(', ', array_merge(...array_values($e->errors()))),
                'data' => null,
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in updateBulanTahun', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'timestamp' => now(),
            ]);
            return response()->json([
                'message' => 'Gagal memperbarui bulan dan tahun: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Fetch the active BulanTahun and available years.
     */
    public function get(): JsonResponse
    {
        try {
            $data = Cache::remember('bt_aktif', now()->addHours(24), function () {
                Log::info('BulanTahun aktif fetched from database', ['timestamp' => now()]);

                $btAktif = BulanTahun::where('aktif', 1)->first();
                $minTahun = BulanTahun::min('tahun') ?? now()->year;
                $maxTahun = BulanTahun::max('tahun') ?? now()->year;

                return [
                    'bt_aktif' => $btAktif,
                    'tahun' => range(max($minTahun - 2, 2000), $maxTahun + 2), // edit here: Prevent negative years
                ];
            });

            // edit here: Handle no active BulanTahun
            if (!$data['bt_aktif']) {
                return response()->json([
                    'message' => 'Tidak ada periode aktif tersedia.',
                    'data' => [
                        'bt_aktif' => null,
                        'tahun' => $data['tahun'],
                    ],
                ], 200);
            }

            return response()->json([
                'message' => 'Data bulan dan tahun aktif berhasil diambil.',
                'data' => BulanTahunResource::make($data), // edit here: Use resource
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in getBulanTahun', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'timestamp' => now(),
            ]);
            return response()->json([
                'message' => 'Gagal mengambil data bulan dan tahun: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Clear the BulanTahun cache.
     */
    private function clearBulanTahunCache(): void
    {
        Cache::forget('bt_aktif');
        Cache::forget('rekonsiliasi_aktif');
        Log::info('Cache cleared for bt_aktif & rekonsiliasi_aktif', ['timestamp' => now()]);
    }
}
