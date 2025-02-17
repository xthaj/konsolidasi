<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\Wilayah;
use App\Models\Inflasi;
use Illuminate\Http\Request;

class RekonsiliasiController extends Controller
{
    public function pemilihan()
    {
        $wilayah = Wilayah::all();
        $komoditas = Komoditas::all();
        $inflasi = Inflasi::with('komoditas')->paginate(25);
        return view('rekonsiliasi.pemilihan', compact('wilayah', 'komoditas', 'inflasi'));

    }

    public function progres()
    {
        return view('rekonsiliasi.progres');
    }
}
