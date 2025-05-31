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

    // can be improved - delete unnecessary fields
    public function findInflasiId(Request $request)
    {
        try {
            $combinations = $request->json()->all();

            if (!is_array($combinations) || empty($combinations)) {
                return response()->json([
                    'error' => 'Invalid or empty combinations array',
                    'message' => 'Harap masukkan kombinasi data yang valid.'
                ], 400);
            }

            $results = [];

            foreach ($combinations as $combo) {
                $bulan = $combo['bulan'] ?? null;
                $tahun = $combo['tahun'] ?? null;
                $kd_level = $combo['kd_level'] ?? null;
                $kd_wilayah = $combo['kd_wilayah'] ?? null;
                $kd_komoditas = $combo['kd_komoditas'] ?? null;
                $level_harga = $combo['level_harga'] ?? 'Unknown Level Harga';
                $nama_komoditas = $combo['nama_komoditas'] ?? 'Unknown Komoditas';

                if (!$bulan || !$tahun || !$kd_level || !$kd_wilayah || !$kd_komoditas) {
                    $results[] = [
                        'kd_wilayah' => $kd_wilayah,
                        'kd_komoditas' => $kd_komoditas,
                        'level_harga' => $level_harga,
                        'nama_komoditas' => $nama_komoditas,
                        'error' => 'Missing required parameters',
                        'message' => 'Data tidak lengkap: pastikan bulan, tahun, level harga, wilayah, dan komoditas diisi.',
                        'inflasi_id' => null
                    ];
                    continue;
                }

                $bulanTahun = BulanTahun::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->first();

                if (!$bulanTahun) {
                    $results[] = [
                        'kd_wilayah' => $kd_wilayah,
                        'kd_komoditas' => $kd_komoditas,
                        'level_harga' => $level_harga,
                        'nama_komoditas' => $nama_komoditas,
                        'message' => 'Periode bulan dan tahun tidak ditemukan.',
                        'inflasi_id' => null
                    ];
                    continue;
                }

                $inflasi = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                    ->where('kd_level', $kd_level)
                    ->where('kd_wilayah', $kd_wilayah)
                    ->where('kd_komoditas', $kd_komoditas)
                    ->first();

                $wilayah = Wilayah::where('kd_wilayah', $kd_wilayah)->first();
                $nama_wilayah = $wilayah ? $wilayah->nama_wilayah : 'Unknown Wilayah';

                if ($inflasi) {
                    $results[] = [
                        'bulan_tahun_id' => $bulanTahun->bulan_tahun_id,
                        'kd_wilayah' => $kd_wilayah,
                        'kd_komoditas' => $kd_komoditas,
                        'nama_wilayah' => $nama_wilayah,
                        'level_harga' => $level_harga,
                        'nama_komoditas' => $nama_komoditas,
                        'inflasi_id' => $inflasi->inflasi_id,
                        'inflasi' => $inflasi->nilai_inflasi ?? "0.00",
                    ];
                } else {
                    $results[] = [
                        'bulan_tahun_id' => $bulanTahun->bulan_tahun_id,
                        'kd_wilayah' => $kd_wilayah,
                        'kd_komoditas' => $kd_komoditas,
                        'nama_wilayah' => $nama_wilayah,
                        'level_harga' => $level_harga,
                        'nama_komoditas' => $nama_komoditas,
                        'message' => 'Data inflasi tidak ditemukan untuk kombinasi ini.',
                        'inflasi_id' => null
                    ];
                }
            }

            return response()->json($results, 200);
        } catch (\Exception $e) {
            Log::error('Error in findInflasiId: ' . $e->getMessage(), [
                'combinations' => $request->json()->all()
            ]);
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Terjadi kesalahan server. Silakan coba lagi nanti.'
            ], 500);
        }
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

    public function confirmRekonsiliasi(Request $request)
    {
        Log::info('Confirm rekonsiliasi started', ['request' => $request->all()]);

        $validated = $request->validate([
            'inflasi_ids' => 'required|array',
            'inflasi_ids.*' => 'integer',
            'bulan_tahun_ids' => 'required|array',
            'bulan_tahun_ids.*' => 'integer',
        ]);

        try {
            DB::beginTransaction();

            $existingRecords = Rekonsiliasi::whereIn('inflasi_id', $validated['inflasi_ids'])
                ->whereIn('bulan_tahun_id', $validated['bulan_tahun_ids'])
                ->select('inflasi_id', 'bulan_tahun_id')
                ->get()
                ->groupBy('bulan_tahun_id')
                ->mapWithKeys(function ($group, $bulan_tahun_id) {
                    return [$bulan_tahun_id => $group->pluck('inflasi_id')->toArray()];
                })
                ->toArray();

            $duplicates = [];
            $createdCount = 0;
            $inputPairs = array_combine($validated['inflasi_ids'], $validated['bulan_tahun_ids']);

            $inflasiDetails = DB::table('inflasi')
                ->join('wilayah', 'inflasi.kd_wilayah', '=', 'wilayah.kd_wilayah')
                ->join('komoditas', 'inflasi.kd_komoditas', '=', 'komoditas.kd_komoditas')
                ->whereIn('inflasi.inflasi_id', $validated['inflasi_ids'])
                ->select(
                    'inflasi.inflasi_id as inflasi_id',
                    'wilayah.nama_wilayah',
                    'komoditas.nama_komoditas'
                )
                ->get()
                ->keyBy('inflasi_id')
                ->toArray();

            foreach ($inputPairs as $inflasi_id => $bulan_tahun_id) {
                if (isset($existingRecords[$bulan_tahun_id]) && in_array($inflasi_id, $existingRecords[$bulan_tahun_id])) {
                    $duplicates[] = [
                        'inflasi_id' => $inflasi_id,
                        'bulan_tahun_id' => $bulan_tahun_id,
                        'nama_wilayah' => $inflasiDetails[$inflasi_id]->nama_wilayah ?? 'Unknown',
                        'nama_komoditas' => $inflasiDetails[$inflasi_id]->nama_komoditas ?? 'Unknown',
                    ];
                    continue;
                }

                Rekonsiliasi::create([
                    'inflasi_id' => $inflasi_id,
                    'bulan_tahun_id' => $bulan_tahun_id,
                    'terakhir_diedit' => now(),
                ]);
                $createdCount++;
            }

            DB::commit();

            if (!empty($duplicates)) {
                $duplicateList = implode(', ', array_map(fn($d) => "{$d['nama_wilayah']} - {$d['nama_komoditas']}", $duplicates));
                return response()->json([
                    'success' => true,
                    'partial_success' => true,
                    'message' => "Pemilihan komoditas rekonsiliasi berhasil untuk {$createdCount} entri. " .
                        count($duplicates) . " entri dilewati: {$duplicateList}.",
                    'duplicates' => $duplicates,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => "Pemilihan komoditas rekonsiliasi berhasil untuk {$createdCount} entri.",
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pemilihan komoditas rekonsiliasi gagal dilakukan:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pemilihan komoditas rekonsiliasi: ' . $e->getMessage(),
            ], 500);
        }
    }
}
