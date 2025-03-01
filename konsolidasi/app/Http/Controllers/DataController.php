<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\Wilayah;
use App\Models\Inflasi;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DataImport;

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

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:2100',
            'level' => 'required|string|in:01,02,03,04,05',
        ]);

        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        try {
            $import = new DataImport($request->bulan, $request->tahun, $request->level);
            Excel::import($import, $request->file('file'));

            if ($import->getErrors()->isNotEmpty()) {
                return redirect()->back()->with('errors', $import->getErrors());
            }

            return redirect()->back()->with('success', 'Data imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing data: ' . $e->getMessage());
        }
    }
}
