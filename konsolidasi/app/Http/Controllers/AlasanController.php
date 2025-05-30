<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlasanResource;
use App\Models\Alasan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class AlasanController extends Controller
{
    /**
     * Store a new alasan.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('Starting storeAlasan', [
            'request_data' => $request->all(),
            'timestamp' => now(),
        ]);

        try {
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
            ], [
                'nama.max' => 'Alasan tidak boleh melebihi 255 karakter.',
            ]);

            $alasan = Alasan::create([
                'keterangan' => $validated['nama'],
            ]);

            $this->clearAlasanCache();

            Log::info('Alasan created', ['alasan' => $alasan->toArray()]);

            return response()->json([
                'message' => 'Alasan berhasil ditambahkan.',
                'data' => null,
            ], 201);
        } catch (ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            $errorMessage = $e->errors()['nama'][0] ?? 'Validasi gagal.';
            return response()->json([
                'message' => $errorMessage,
                'data' => null,
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating alasan', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Gagal menambahkan alasan: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Delete an alasan.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        Log::info('Starting deleteAlasan', [
            'id' => $id,
            'timestamp' => now(),
        ]);

        $alasan = Alasan::find($id);
        if (!$alasan) {
            Log::warning('Alasan not found for deletion', ['id' => $id]);
            return response()->json([
                'message' => 'Alasan tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        try {
            $alasan->delete();
            $this->clearAlasanCache();

            Log::info('Alasan deleted', ['id' => $id]);

            return response()->json([
                'message' => 'Alasan berhasil dihapus.',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting alasan', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'id' => $id,
            ]);

            return response()->json([
                'message' => 'Gagal menghapus alasan: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Clear the alasan cache.
     *
     * @return void
     */
    private function clearAlasanCache()
    {
        Cache::forget('alasan_data');
        Log::info('Cache cleared for alasan_data');
    }

    public function getAllAlasan()
    {
        try {
            Log::info('Alasan data NOT fetched from database', ['timestamp' => now()]);
            $data = Cache::rememberForever('alasan_data', function () {
                Log::info('Alasan data fetched from database', ['timestamp' => now()]);
                return Alasan::all();
            });

            return response()->json([
                'message' => 'Alasan data retrieved successfully',
                'data' => AlasanResource::collection($data)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching alasan data', [
                'error' => $e->getMessage(),
                'timestamp' => now()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve alasan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        return view('master.alasan');
    }
}
