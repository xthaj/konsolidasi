<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Alasan;
use Illuminate\Validation\ValidationException;

class AlasanController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Starting storeAlasan', [
            'request_data' => $request->all(),
            'timestamp' => now()
        ]);

        try {
            $request->validate([
                'nama' => 'required|string|max:255',
            ], [
                'nama.max' => 'Alasan tidak boleh melebihi 255 karakter.',
            ]);
            Log::info('Request validated successfully', $request->all());
        } catch (ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->errors()['nama'][0] ?? 'Validasi gagal.',
            ], 422);
        }

        try {
            $alasan = Alasan::create([
                'keterangan' => $request->input('nama'),
            ]);

            Cache::forget('alasan_data');
            Log::info('Cache cleared for alasan_data');

            Log::info('Alasan created successfully', ['alasan' => $alasan->toArray()]);
            return response()->json([
                'status' => 'success',
                'message' => 'Alasan berhasil ditambahkan.',
                'data' => null,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating alasan', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan alasan.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        Log::info('Starting deleteAlasan', [
            'id' => $id,
            'timestamp' => now()
        ]);

        $alasan = Alasan::find($id);
        if (!$alasan) {
            Log::warning('Alasan not found for deletion', ['id' => $id]);
            return response()->json([
                'status' => 'error',
                'message' => 'Alasan tidak ditemukan.'
            ], 404);
        }

        try {
            $alasan->delete();

            Cache::forget('alasan_data');
            Log::info('Cache cleared for alasan_data');

            Log::info('Alasan deleted successfully', ['id' => $id]);
            return response()->json([
                'status' => 'success',
                'message' => 'Alasan berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting alasan', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'id' => $id
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus alasan.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
