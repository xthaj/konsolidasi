<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\View\View;


class VisualisasiController extends Controller
{
    public function create(): View
    {
        $wilayah = Wilayah::all();
        $komoditas = Komoditas::all();
        return view('visualisasi.harmonisasi', compact('wilayah', 'komoditas'));
    }
}
