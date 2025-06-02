<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\Wilayah;
use App\Models\Inflasi;
use App\Models\BulanTahun;
use App\Models\LevelHarga;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DataImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

// Add this import if the FinalImport class exists at App\Imports\FinalImport
use App\Imports\FinalImport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use App\Models\Rekonsiliasi;
use Illuminate\Support\Facades\DB;
use App\Exports\InflasiExport;
use App\Http\Resources\InflasiAllLevelResource;
use Illuminate\Support\Facades\Redirect;
use App\Http\Resources\InflasiResource;

class DataController extends Controller
{

    public function hapus_final(Request $request)
    {
        Log::info('Hapus final method started', $request->all());

        $request->merge(['bulan' => (int) $request->bulan]);

        $validated = $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:2100',
            'level' => 'required|string|in:01,02,03,04,05',
        ]);

        $response = [
            'success' => false,
            'message' => [],
            'data' => [],
        ];

        try {
            $bulanTahun = BulanTahun::where('bulan', $validated['bulan'])
                ->where('tahun', $validated['tahun'])
                ->first();

            if (!$bulanTahun) {
                $response['message'] = ["Tidak ada data tersedia untuk periode tersebut."];
                return $request->wantsJson()
                    ? response()->json($response)
                    : redirect()->back()->with('response', $response);
            }

            // Update only the final_inflasi and final_andil columns to NULL
            $updatedRows = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->where('kd_level', $validated['level'])
                ->update([
                    'final_inflasi' => null,
                    'final_andil' => null,
                ]);

            if ($updatedRows > 0) {
                $response['success'] = true;
                $response['message'] = ["Data berhasil dihapus sebagian. Kolom final_inflasi dan final_andil diset NULL. Jumlah baris terpengaruh: $updatedRows"];
                $response['data'] = ['updated' => $updatedRows];
            } else {
                $response['message'] = ["Tidak ada data yang sesuai untuk diubah."];
            }
        } catch (\Exception $e) {
            $response['message'] = ["Terjadi kesalahan saat mengubah data: {$e->getMessage()}"];
            Log::error("Hapus final error: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
        }

        return $request->wantsJson()
            ? response()->json($response)
            : redirect()->back()->with('response', $response);
    }

    public function store(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:2100',
            'kd_level' => 'required|string|in:01,02,03,04,05',
            'kd_wilayah' => 'required|string',
            'kd_komoditas' => 'required|integer',
            'inflasi' => 'required|numeric',
        ]);

        try {
            $bulanTahun = BulanTahun::firstOrCreate([
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
            ]);

            Inflasi::create([
                'bulan_tahun_id' => $bulanTahun->bulan_tahun_id,
                'kd_level' => $request->kd_level,
                'kd_wilayah' => $request->kd_wilayah,
                'kd_komoditas' => $request->kd_komoditas,
                'inflasi' => $request->inflasi,
            ]);

            return redirect()->back()->with('success', 'Data added successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error adding data: ' . $e->getMessage());
        }
    }
}
