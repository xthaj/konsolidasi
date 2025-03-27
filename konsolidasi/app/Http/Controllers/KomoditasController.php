<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Komoditas;

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
            ]);
            Log::info('Request validated successfully', $request->all());
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'The provided data is invalid.',
                'details' => $e->errors()
            ], 422);
        }

        try {
            // Generate kd_komoditas as a padded string (e.g., "001", "002")
            $lastKomoditas = Komoditas::orderBy(DB::raw('CAST(kd_komoditas AS SIGNED)'), 'desc')->first();

            $lastNumber = $lastKomoditas ? (int) $lastKomoditas->kd_komoditas : 0; // Convert string to int
            $nextNumber = $lastNumber + 1;
            $kd_komoditas = str_pad($nextNumber, 3, '0', STR_PAD_LEFT); // e.g., "001"

            $komoditas = Komoditas::create([
                'kd_komoditas' => $kd_komoditas,
                'nama_komoditas' => $request->input('nama_komoditas'),
            ]);

            // Clear the cache after successful creation
            Cache::forget('komoditas_data');
            Log::info('Cache cleared for komoditas_data');

            Log::info('Komoditas created successfully', ['komoditas' => $komoditas->toArray()]);
            return response()->json($komoditas, 201);
        } catch (\Exception $e) {
            Log::error('Error creating komoditas', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to create komoditas',
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
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
                'error' => 'Komoditas not found',
                'message' => 'The specified komoditas does not exist.'
            ], 404);
        }

        try {
            $request->validate([
                'nama_komoditas' => 'required|string|max:255',
                // kd_komoditas is not validated or updated since it cannot change
            ]);
            Log::info('Request validated successfully', $request->all());
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'The provided data is invalid.',
                'details' => $e->errors()
            ], 422);
        }

        try {
            // Only update nama_komoditas
            $komoditas->update([
                'nama_komoditas' => $request->input('nama_komoditas')
            ]);

            // Clear the cache after successful creation
            Cache::forget('komoditas_data');
            Log::info('Cache cleared for komoditas_data');

            Log::info('Komoditas updated successfully', ['komoditas' => $komoditas->toArray()]);
            return response()->json($komoditas, 200);
        } catch (\Exception $e) {
            Log::error('Error updating komoditas', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to update komoditas',
                'message' => 'An unexpected error occurred.',
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
                'error' => 'Komoditas not found',
                'message' => 'The specified komoditas does not exist.'
            ], 404);
        }

        try {
            $komoditas->delete();

            // Clear the cache after successful creation
            Cache::forget('komoditas_data');
            Log::info('Cache cleared for komoditas_data');

            Log::info('Komoditas deleted successfully', ['kd_komoditas' => $kd_komoditas]);
            return response()->json([
                'message' => 'Komoditas deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting komoditas', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'kd_komoditas' => $kd_komoditas
            ]);
            return response()->json([
                'error' => 'Failed to delete komoditas',
                'message' => 'An unexpected error occurred while deleting the komoditas.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
