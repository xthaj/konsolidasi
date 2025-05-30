<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\KomoditasResource;

class KomoditasController extends Controller
{
    public function index()
    {
        return view('master.komoditas');
    }

    /**
     * Store a new komoditas.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('Starting storeKomoditas', [
            'request_data' => $request->all(),
            'timestamp' => now(),
        ]);

        try {
            $validated = $request->validate([
                'nama_komoditas' => 'required|string|max:255',
            ], [
                'nama_komoditas.max' => 'Nama komoditas tidak boleh melebihi 255 karakter.',
            ]);

            $lastKomoditas = Komoditas::orderBy(DB::raw('CAST(kd_komoditas AS INT)'), 'desc')->first();
            $lastNumber = $lastKomoditas ? (int) $lastKomoditas->kd_komoditas : 0;
            $kd_komoditas = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

            $komoditas = Komoditas::create([
                'kd_komoditas' => $kd_komoditas,
                'nama_komoditas' => $validated['nama_komoditas'],
            ]);

            $this->clearKomoditasCache();

            Log::info('Komoditas created', ['komoditas' => $komoditas->toArray()]);

            return response()->json([
                'message' => 'Komoditas berhasil ditambahkan.',
                'data' => null,
            ], 201);
        } catch (ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            $errorMessage = $e->errors()['nama_komoditas'][0] ?? 'Validasi gagal.';
            return response()->json([
                'message' => $errorMessage,
                'data' => null,
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating komoditas', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Gagal menambahkan komoditas: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Update an existing komoditas.
     *
     * @param Request $request
     * @param string $kd_komoditas
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $kd_komoditas)
    {
        Log::info('Starting updateKomoditas', [
            'kd_komoditas' => $kd_komoditas,
            'request_data' => $request->all(),
            'timestamp' => now(),
        ]);

        $komoditas = Komoditas::find($kd_komoditas);
        if (!$komoditas) {
            Log::warning('Komoditas not found', ['kd_komoditas' => $kd_komoditas]);
            return response()->json([
                'message' => 'Komoditas tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        try {
            $validated = $request->validate([
                'nama_komoditas' => 'required|string|max:255',
            ]);

            $komoditas->update([
                'nama_komoditas' => $validated['nama_komoditas'],
            ]);

            $this->clearKomoditasCache();

            Log::info('Komoditas updated', ['komoditas' => $komoditas->toArray()]);

            return response()->json([
                'message' => 'Komoditas berhasil diperbarui.',
                'data' => null,
            ], 200);
        } catch (ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            $errorMessage = $e->errors()['nama_komoditas'][0] ?? 'Validasi gagal.';
            return response()->json([
                'message' => $errorMessage,
                'data' => null,
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating komoditas', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Gagal memperbarui komoditas: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Delete a komoditas.
     *
     * @param string $kd_komoditas
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($kd_komoditas)
    {
        Log::info('Starting deleteKomoditas', [
            'kd_komoditas' => $kd_komoditas,
            'timestamp' => now(),
        ]);

        $komoditas = Komoditas::find($kd_komoditas);
        if (!$komoditas) {
            Log::warning('Komoditas not found for deletion', ['kd_komoditas' => $kd_komoditas]);
            return response()->json([
                'message' => 'Komoditas tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        try {
            $komoditas->delete();
            $this->clearKomoditasCache();

            Log::info('Komoditas deleted', ['kd_komoditas' => $kd_komoditas]);

            return response()->json([
                'message' => 'Komoditas berhasil dihapus.',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting komoditas', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'kd_komoditas' => $kd_komoditas,
            ]);

            return response()->json([
                'message' => 'Gagal menghapus komoditas: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Clear the komoditas cache.
     *
     * @return void
     */
    private function clearKomoditasCache()
    {
        Cache::forget('komoditas_data');
        Log::info('Cache cleared for komoditas_data');
    }

    public function getAllKomoditas()
    {
        try {
            Log::info('Komoditas data NOT fetched from database', ['timestamp' => now()]);
            $data = Cache::rememberForever('komoditas_data', function () {
                Log::info('Komoditas data fetched from database', ['timestamp' => now()]);
                return Komoditas::all();
            });

            return response()->json([
                'message' => 'Komoditas data retrieved successfully',
                'data' => KomoditasResource::collection($data)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching komoditas data', [
                'error' => $e->getMessage(),
                'timestamp' => now()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve komoditas data: ' . $e->getMessage()
            ], 500);
        }
    }
}
