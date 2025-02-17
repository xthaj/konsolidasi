<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\Wilayah;
use App\Models\Inflasi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataController extends Controller
{
    public function edit(): View
    {
        $wilayah = Wilayah::all();
        $komoditas = Komoditas::all();
        $inflasi = Inflasi::with('komoditas')->paginate(25); // Use pagination
        return view('data.edit', compact('wilayah', 'komoditas', 'inflasi'));
    }

    public function create(): View
    {
//        $wilayah = Wilayah::all();
        return view('data.create');
    }
}
