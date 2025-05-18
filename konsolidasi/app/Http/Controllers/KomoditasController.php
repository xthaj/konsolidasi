<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Komoditas;
use Illuminate\Validation\ValidationException;

class KomoditasController extends Controller
{

    public function store(Request $request)
    {
        Log::info('Starting storeKomoditas', [
            'request_data' => $request->all(),
            'timestamp' => now()
        ]);

        try {
            $request->validate([
                'nama_komoditas' => 'required|string|max:255',
            ], [
                'nama_komoditas.max' => 'Nama komoditas tidak boleh melebihi 255 karakter.',
            ]);
            Log::info('Request validated successfully', $request->all());
        } catch (ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->errors()['nama_komoditas'][0] ?? 'Validasi gagal.',
            ], 422);
        }

        try {
            $lastKomoditas = Komoditas::orderBy(DB::raw('CAST(kd_komoditas AS SIGNED)'), 'desc')->first();
            $lastNumber = $lastKomoditas ? (int) $lastKomoditas->kd_komoditas : 0;
            $nextNumber = $lastNumber + 1;
            $kd_komoditas = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $komoditas = Komoditas::create([
                'kd_komoditas' => $kd_komoditas,
                'nama_komoditas' => $request->input('nama_komoditas'),
            ]);

            Cache::forget('komoditas_data');
            Log::info('Cache cleared for komoditas_data');

            Log::info('Komoditas created successfully', ['komoditas' => $komoditas->toArray()]);
            return response()->json([
                'status' => 'success',
                'message' => 'Komoditas berhasil ditambahkan.',
                'data' => null,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating komoditas', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan komoditas.',
            ], 500);
        }
    }

    public function update(Request $request, $kd_komoditas)
    {
        Log::info('Starting updateKomoditas', [
            'kd_komoditas' => $kd_komoditas,
            'request_data' => $request->all(),
            'timestamp' => now()
        ]);

        $komoditas = Komoditas::find($kd_komoditas);
        if (!$komoditas) {
            Log::warning('Komoditas not found', ['kd_komoditas' => $kd_komoditas]);
            return response()->json([
                'status' => 'error',
                'message' => 'Komoditas tidak ditemukan.'
            ], 404);
        }

        try {
            $request->validate([
                'nama_komoditas' => 'required|string|max:255',
            ]);
            Log::info('Request validated successfully', $request->all());
        } catch (ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Nama komoditas terlalu panjang.',
                'details' => $e->errors()
            ], 422);
        }

        try {
            $komoditas->update([
                'nama_komoditas' => $request->input('nama_komoditas')
            ]);

            Cache::forget('komoditas_data');
            Log::info('Cache cleared for komoditas_data');

            Log::info('Komoditas updated successfully', ['komoditas' => $komoditas->toArray()]);
            return response()->json([
                'status' => 'success',
                'message' => 'Komoditas berhasil diperbarui.',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating komoditas', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui komoditas.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy($kd_komoditas)
    {
        Log::info('Starting deleteKomoditas', [
            'kd_komoditas' => $kd_komoditas,
            'timestamp' => now()
        ]);

        $komoditas = Komoditas::find($kd_komoditas);
        if (!$komoditas) {
            Log::warning('Komoditas not found for deletion', ['kd_komoditas' => $kd_komoditas]);
            return response()->json([
                'status' => 'error',
                'message' => 'Komoditas tidak ditemukan.'
            ], 404);
        }

        try {
            $komoditas->delete();

            Cache::forget('komoditas_data');
            Log::info('Cache cleared for komoditas_data');

            Log::info('Komoditas deleted successfully', ['kd_komoditas' => $kd_komoditas]);
            return response()->json([
                'status' => 'success',
                'message' => 'Komoditas berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting komoditas', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'kd_komoditas' => $kd_komoditas
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus komoditas.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
