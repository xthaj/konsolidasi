<?php

namespace App\Http\Controllers;

use App\Http\Resources\BulanTahunResource;
use App\Models\BulanTahun;
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

    public function update(Request $request)
    {
        Log::info('Starting updateBulanTahun', [
            'request_data' => $request->all(),
            'timestamp' => now()
        ]);

        // Validate request
        try {
            $validated = $request->validate([
                'bulan' => 'required|string|in:01,02,03,04,05,06,07,08,09,10,11,12',
                'tahun' => 'required|digits:4',
            ]);
            Log::info('Request validated successfully', $validated);
        } catch (ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'timestamp' => now()
            ]);
            return response()->json([
                'message' => 'Validation failed: ' . implode(', ', array_merge(...array_values($e->errors())))
            ], 422);
        }

        $bulan = ltrim($validated['bulan'], '0');
        $tahun = $validated['tahun'];

        // Check for existing record
        $existing = BulanTahun::where('bulan', $bulan)->where('tahun', $tahun)->first();
        Log::info('Checked for existing record', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'existing' => $existing ? $existing->toArray() : null,
            'timestamp' => now()
        ]);

        // Check current active record
        $currentActive = BulanTahun::where('aktif', 1)->first();
        Log::info('Fetched current active record', [
            'current_active' => $currentActive ? $currentActive->toArray() : null,
            'timestamp' => now()
        ]);

        if ($existing && $existing->aktif == 1) {
            Log::warning('Attempted to update already active period', [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'timestamp' => now()
            ]);
            return response()->json([
                'message' => 'Bulan dan tahun ini sudah aktif'
            ], 422);
        }

        try {
            // Start transaction
            DB::beginTransaction();

            // Deactivate current active record
            BulanTahun::where('aktif', 1)->update(['aktif' => 0]);
            Log::info('Deactivated current active record', ['timestamp' => now()]);

            // Update or create record
            if ($existing) {
                $existing->update(['aktif' => 1]);
                Log::info('Updated existing record to active', [
                    'record' => $existing->toArray(),
                    'timestamp' => now()
                ]);
            } else {
                $newRecord = BulanTahun::create([
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'aktif' => 1,
                ]);
                Log::info('Created new active record', [
                    'record' => $newRecord->toArray(),
                    'timestamp' => now()
                ]);
            }

            // Clear cache
            Cache::forget('bt_aktif');
            Log::info('Cache cleared for bt_aktif', ['timestamp' => now()]);

            DB::commit();

            return response()->json([
                'message' => 'Bulan dan tahun berhasil diperbarui',
                'data' => [
                    'bulan' => $validated['bulan'],
                    'tahun' => $tahun
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error during update process', [
                'error' => $e->getMessage(),
                'bulan' => $bulan,
                'tahun' => $tahun,
                'timestamp' => now()
            ]);
            return response()->json([
                'message' => 'Failed to update bulan and tahun: ' . $e->getMessage()
            ], 500);
        }
    }

    public function get()
    {
        try {
            Log::info('BT aktif data NOT fetched from database', ['timestamp' => now()]);

            $data = Cache::remember('bt_aktif', now()->addWeek(), function () {
                Log::info('BT aktif fetched from database', ['timestamp' => now()]);

                $btAktif = BulanTahun::where('aktif', 1)->first();
                $minTahun = BulanTahun::min('tahun') ?? now()->year;
                $maxTahun = BulanTahun::max('tahun') ?? now()->year;

                return [
                    'bt_aktif' => $btAktif,
                    'tahun' => range($minTahun - 2, $maxTahun + 2),
                ];
            });

            return response()->json([
                'message' => 'Bulan dan tahun aktif retrieved successfully',
                'data' => BulanTahunResource::make($data)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching bulan tahun data', [
                'error' => $e->getMessage(),
                'timestamp' => now()
            ]);
            return response()->json([
                'message' => 'Failed to retrieve bulan tahun data: ' . $e->getMessage()
            ], 500);
        }
    }
}
