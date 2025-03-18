<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\Wilayah;
use App\Models\Inflasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekonsiliasiController extends Controller
{
    public function pemilihan()
    {

        $inflasi = Inflasi::with('komoditas')->paginate(25);
        return view('rekonsiliasi.pemilihan', compact('wilayah', 'komoditas', 'inflasi'));
    }



    public function progres()
    {
        return view('rekonsiliasi.progres');
    }

    public function index( request -> send bulan tahun)
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
