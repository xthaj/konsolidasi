<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\Wilayah;
use App\Models\Inflasi;
use App\Models\BulanTahun;
use App\Models\Rekonsiliasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekonsiliasiController extends Controller
{
    public function pemilihan()
    {
        return view('rekonsiliasi.pemilihan');
    }

    public function progres(Request $request)
    {

        // Fetch active BulanTahun for defaults
        $activeBulanTahun = BulanTahun::where('aktif', 1)->first();
        $defaultBulan = $activeBulanTahun ? $activeBulanTahun->bulan : '01';
        $defaultTahun = $activeBulanTahun ? $activeBulanTahun->tahun : now()->year;
        $defaultKdLevel = '01'; // Harga Konsumen Kota
        $defaultKdWilayah = ''; // All regions
        $defaultStatus = 'all';

        if (!$request->has('bulan')) {
            return redirect()->route('rekon.progres', [
                'bulan' => $defaultBulan,
                'tahun' => $defaultTahun,
                'kd_level' => $defaultKdLevel,
                'kd_wilayah' => $defaultKdWilayah,
                'status' => $defaultStatus,
            ]);
        }

        // Get filter inputs or use defaults
        $bulan = $request->input('bulan', $defaultBulan);
        $tahun = $request->input('tahun', $defaultTahun);
        $kdLevel = $request->input('kd_level', $defaultKdLevel);
        $kdWilayah = $request->input('kd_wilayah', $defaultKdWilayah);
        $status = $request->input('status', $defaultStatus);

        // Find BulanTahun record
        $bulanTahun = BulanTahun::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        // Default response
        $response = [
            'rekonsiliasi' => null,
            'message' => 'Silakan isi filter untuk menampilkan data rekonsiliasi.',
            'status' => 'no_filters',
            'filters' => compact('bulan', 'tahun', 'kdLevel', 'kdWilayah', 'status'),
        ];

        if ($bulanTahun) {
            // Build query with eager loading
            $rekonQuery = Rekonsiliasi::with(['inflasi.komoditas', 'inflasi.wilayah', 'user'])
                ->where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                ->whereHas('inflasi', function ($query) use ($kdLevel) {
                    $query->where('kd_level', $kdLevel);
                });

            // Apply filters
            if ($kdWilayah && $kdWilayah !== '0') {
                $rekonQuery->whereHas('inflasi', function ($query) use ($kdWilayah) {
                    $query->where('kd_wilayah', $kdWilayah);
                });
            }

            if ($status !== 'all') {
                $rekonQuery->where(function ($query) use ($status) {
                    $status === '01' ? $query->whereNull('user_id') : $query->whereNotNull('user_id');
                });
            }

            // Paginate results
            $rekonsiliasi = $rekonQuery->paginate(75);

            // Fetch opposite inflation level
            $inflasiOpposite = null;
            if (in_array($kdLevel, ['01', '02'])) {
                $oppositeLevel = $kdLevel === '01' ? '02' : '01';
                $inflasiOpposite = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                    ->where('kd_level', $oppositeLevel)
                    ->where('kd_wilayah', $kdWilayah)
                    ->whereIn('kd_komoditas', $rekonsiliasi->pluck('inflasi.kd_komoditas'))
                    ->get()
                    ->keyBy('kd_komoditas');
            }

            // Update response
            $response['rekonsiliasi'] = $rekonsiliasi;
            $response['inflasi_opposite'] = $inflasiOpposite;
            $response['message'] = $rekonsiliasi->isEmpty() ? 'Tidak ada data untuk filter ini.' : 'Data berhasil dimuat.';
            $response['status'] = $rekonsiliasi->isEmpty() ? 'no_data' : ($rekonsiliasi->first()->user_id ? 'sudah_diisi' : 'belum_diisi');
        } elseif (!$bulanTahun) {
            $response['message'] = 'Periode tidak ditemukan.';
            $response['status'] = 'no_period';
        }

        return view('rekonsiliasi.progres', $response);
    }
    public function index()
    {
        $rekonsiliasiData = DB::table('rekonsiliasi')
            ->leftJoin('inflasi', 'rekonsiliasi.inflasi_id', '=', 'inflasi.inflasi_id')
            ->leftJoin('wilayah', 'inflasi.kd_wilayah', '=', 'wilayah.kd_wilayah')
            ->leftJoin('komoditas', 'inflasi.kd_komoditas', '=', 'komoditas.kd_komoditas')
            ->leftJoin('users', 'rekonsiliasi.user_id', '=', 'users.id')
            ->select(
                'rekonsiliasi.rekonsiliasi_id',
                'inflasi.kd_wilayah',
                'wilayah.nama_wilayah',
                'inflasi.kd_komoditas',
                'komoditas.nama_komoditas',
                'inflasi.kd_level',
                'inflasi.inflasi',
                'rekonsiliasi.alasan',
                'rekonsiliasi.detail',
                'rekonsiliasi.media',
                'rekonsiliasi.terakhir_diedit',
                'users.name as user_name'
            )
            ->get();

        $levelHargaMapping = [
            '01' => 'Harga Konsumen Kota',
            '02' => 'Harga Konsumen Desa',
            '03' => 'Harga Perdagangan Besar',
            '04' => 'Harga Produsen Desa',
            '05' => 'Harga Produsen'
        ];

        return view('rekonsiliasi.index', compact('rekonsiliasiData', 'levelHargaMapping'));
    }
}
