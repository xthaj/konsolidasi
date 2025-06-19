<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\KomoditasResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
// ModelNotFoundException

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

            // Find the last kd_komoditas and increment

            $lastKdKomoditas = Komoditas::max('kd_komoditas') ?? 0;
            $newKdKomoditas = $lastKdKomoditas + 1;

            // Create the new komoditas
            $komoditas = Komoditas::create([
                'kd_komoditas' => $newKdKomoditas,
                'nama_komoditas' => $validated['nama_komoditas'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->clearKomoditasCache();

            Log::info('Komoditas created', ['komoditas' => $komoditas->toArray()]);

            return response()->json([
                'message' => 'Komoditas berhasil ditambahkan.',
                'data' => new KomoditasResource($komoditas), // Return the created resource
            ], 201);
        } catch (ValidationException $e) {
            Log::warning('Validation failed in storeKomoditas', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validasi gagal: ' . implode(', ', array_merge(...array_values($e->errors()))),
                'data' => null,
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in storeKomoditas', [
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

        try {
            $komoditas = Komoditas::find($kd_komoditas);
            if (!$komoditas) {
                Log::warning('Komoditas not found', ['kd_komoditas' => $kd_komoditas]);
                return response()->json([
                    'message' => 'Komoditas tidak ditemukan.',
                    'data' => null,
                ], 404);
            }

            $validated = $request->validate([
                'nama_komoditas' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('komoditas', 'nama_komoditas')->ignore($kd_komoditas, 'kd_komoditas'),
                ],
            ], [
                'nama_komoditas.max' => 'Nama komoditas tidak boleh melebihi 255 karakter.',
                'nama_komoditas.unique' => 'Nama komoditas sudah ada.',
            ]);

            if ($komoditas->nama_komoditas === $validated['nama_komoditas']) {
                return response()->json([
                    'message' => 'Tidak ada perubahan pada data komoditas.',
                    'data' => new KomoditasResource($komoditas),
                ], 200);
            }

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
            Log::warning('Validation failed in updateKomoditas', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validasi gagal: ' . implode(', ', array_merge(...array_values($e->errors()))),
                'data' => null,
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in updateKomoditas', [
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
    public function destroy($kd_komoditas): JsonResponse
    {
        Log::info('Starting deleteKomoditas', [
            'kd_komoditas' => $kd_komoditas,
            'timestamp' => now(),
        ]);

        try {
            $komoditas = Komoditas::findOrFail($kd_komoditas);

            // add here: Check for dependencies
            if (DB::table('inflasi')->where('kd_komoditas', $kd_komoditas)->exists()) {
                Log::warning('Cannot delete komoditas due to dependencies', ['kd_komoditas' => $kd_komoditas]);
                return response()->json([
                    'message' => 'Komoditas tidak dapat dihapus karena terkait dengan data inflasi.',
                    'data' => null,
                ], 422);
            }

            $komoditas->delete();
            $this->clearKomoditasCache();

            Log::info('Komoditas deleted', ['kd_komoditas' => $kd_komoditas]);

            return response()->json([
                'message' => 'Komoditas berhasil dihapus.',
                'data' => null,
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::warning('Komoditas not found in deleteKomoditas', ['kd_komoditas' => $kd_komoditas]);
            return response()->json([
                'message' => 'Komoditas tidak ditemukan.',
                'data' => null,
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in deleteKomoditas', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
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
    private function clearKomoditasCache(): void
    {
        Cache::forget('komoditas_data');
        Log::info('Cache cleared for komoditas_data', ['timestamp' => now()]);
    }

    public function getAllKomoditas(): JsonResponse
    {
        try {
            $data = Cache::remember('komoditas_data', now()->addHours(24), function () {
                Log::info('Komoditas data fetched from database', ['timestamp' => now()]);
                return Komoditas::orderBy('kd_komoditas', 'asc')->get();
            });

            // edit here: Handle empty result
            if ($data->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada data komoditas tersedia.',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'message' => 'Data komoditas berhasil diambil.',
                'data' => KomoditasResource::collection($data)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in getAllKomoditas', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Gagal mengambil data komoditas: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}
