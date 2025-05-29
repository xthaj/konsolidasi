<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use App\Http\Resources\WilayahResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WilayahController extends Controller
{
    public function index()
    {
        return view('master.wilayah');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_wilayah' => 'required|string|max:255',
        ]);

        try {
            $wilayah = Wilayah::create([
                'kd_wilayah' => $this->generateKodeWilayah(),
                'nama_wilayah' => $request->nama_wilayah,
                'flag' => $request->flag ?? 2, // Default to 2 if not provided
            ]);

            Cache::forget('wilayah_data'); // Clear cache to refresh data
            return response()->json(['message' => 'Wilayah berhasil ditambahkan', 'data' => new WilayahResource($wilayah)], 201);
        } catch (\Exception $e) {
            Log::error('Failed to add wilayah: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menambahkan wilayah', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $kd_wilayah)
    {
        $request->validate([
            'nama_wilayah' => 'required|string|max:255',
        ]);

        try {
            $wilayah = Wilayah::where('kd_wilayah', $kd_wilayah)->firstOrFail();
            $wilayah->update([
                'nama_wilayah' => $request->nama_wilayah,
            ]);

            Cache::forget('wilayah_data'); // Clear cache to refresh data
            return response()->json(['message' => 'Wilayah berhasil diperbarui', 'data' => new WilayahResource($wilayah)]);
        } catch (\Exception $e) {
            Log::error('Failed to update wilayah: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui wilayah', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($kd_wilayah)
    {
        try {
            $wilayah = Wilayah::where('kd_wilayah', $kd_wilayah)->firstOrFail();
            $wilayah->delete();

            Cache::forget('wilayah_data'); // Clear cache to refresh data
            return response()->json(['message' => 'Wilayah berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Failed to delete wilayah: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus wilayah', 'error' => $e->getMessage()], 500);
        }
    }

    private function generateKodeWilayah()
    {
        // Simple logic to generate unique kode_wilayah (adjust as needed)
        $lastWilayah = Wilayah::orderBy('kd_wilayah', 'desc')->first();
        $lastCode = $lastWilayah ? (int) $lastWilayah->kd_wilayah : 0;
        return str_pad($lastCode + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getAllWilayah()
    {
        Log::info('Wilayah data fetched', ['timestamp' => now()]);

        $data = Cache::rememberForever('all_wilayah_data', function () {
            Log::info('Wilayah data fetched from database', ['timestamp' => now()]);
            return WilayahResource::collection(Wilayah::all());
        });

        return response()->json(['data' => $data]);
    }

    public function getSegmentedWilayah()
    {
        Log::info('Wilayah data NOT fetched from database', ['timestamp' => now()]);

        $data = Cache::rememberForever('wilayah_data', function () {
            Log::info('Wilayah data fetched from database', ['timestamp' => now()]);
            return [
                'provinces' => WilayahResource::collection(Wilayah::where('flag', 2)->get()),
                'kabkots' => WilayahResource::collection(Wilayah::where('flag', 3)->get()),
            ];
        });

        return response()->json(['data' => $data]);
    }
}
