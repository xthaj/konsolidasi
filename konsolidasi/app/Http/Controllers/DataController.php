<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\Wilayah;
use App\Models\Inflasi;
use App\Models\BulanTahun;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DataImport;
use Illuminate\Support\Facades\Log;

class DataController extends Controller
{
    // public function index(Request $request)
    // {
    //     $query = Inflasi::query()->with('komoditas');

    //     // Apply filters if present
    //     if ($request->filled('bulan') && $request->filled('tahun')) {
    //         $periode = "{$request->tahun}-{$request->bulan}-01";
    //         $query->where('periode', $periode);
    //     }
    //     if ($request->filled('level_harga')) {
    //         $query->where('level_harga', $request->level_harga);
    //     }
    //     if ($request->filled('nasional') && $request->nasional == '1') {
    //         $query->where('kd_wilayah', '1'); // Nasional
    //     } elseif ($request->filled('kd_wilayah')) {
    //         $query->where('kd_wilayah', $request->kd_wilayah);
    //     }
    //     if ($request->filled('kd_komoditas')) {
    //         $query->where('kd_komoditas', $request->kd_komoditas);
    //     }

    //     $inflasi = $request->hasAny(['bulan', 'tahun', 'level_harga', 'kd_wilayah', 'kd_komoditas', 'nasional'])
    //         ? $query->paginate(10)
    //         : collect(); // Empty if no filters

    //     $komoditas = Komoditas::all();
    //     return view('your-two-panel-page', compact('inflasi', 'komoditas'));
    // }

    public function edit(Request $request): View
    {
        $response = [
            'inflasi' => null,
            'message' => 'Silakan isi filter di sidebar untuk menampilkan data inflasi.',
            'status' => 'no_filters',
            'bulan' => $request->input('bulan', ''),
            'tahun' => $request->input('tahun', ''),
            'kd_level' => $request->input('kd_level', ''),
            'kd_wilayah' => $request->input('kd_wilayah', ''),
            'kd_komoditas' => $request->input('kd_komoditas', ''),
            'sort' => $request->input('sort', 'kd_komoditas'),
            'direction' => $request->input('direction', 'asc'),
        ];

        if ($request->filled('bulan') &&
            $request->filled('tahun') &&
            $request->filled('kd_level') &&
            $request->filled('kd_wilayah')) {

            $query = Inflasi::query()->with('komoditas');

            $bulanTahun = BulanTahun::where('bulan', $request->bulan)
                ->where('tahun', $request->tahun)
                ->first();

            if ($bulanTahun) {
                $query->where('bulan_tahun_id', $bulanTahun->bulan_tahun_id);
                Log::info('BulanTahun found', ['bulan_tahun_id' => $bulanTahun->bulan_tahun_id]);

                $query->where('kd_level', $request->kd_level);
                $query->where('kd_wilayah', $request->kd_wilayah);

                if ($request->filled('kd_komoditas')) {
                    $query->where('kd_komoditas', $request->kd_komoditas);
                }

                $sortColumn = $request->input('sort', 'kd_komoditas');
                $sortDirection = $request->input('direction', 'asc');
                if (in_array($sortColumn, ['kd_komoditas', 'inflasi'])) {
                    $query->orderBy($sortColumn, $sortDirection);
                } else {
                    $query->orderBy('kd_komoditas', 'asc');
                }

                Log::info('Filters applied', $request->all());
                Log::info('SQL Query', ['query' => $query->toSql(), 'bindings' => $query->getBindings()]);

                $inflasi = $query->paginate(25);
                Log::info('Inflasi result', ['count' => $inflasi->count(), 'data' => $inflasi->toArray()]);

                if ($inflasi->isEmpty()) {
                    $response = [
                        'inflasi' => $inflasi,
                        'message' => 'Data tidak tersedia untuk filter yang dipilih.',
                        'status' => 'no_data',
                        'bulan' => $request->bulan,
                        'tahun' => $request->tahun,
                        'kd_level' => $request->kd_level,
                        'kd_wilayah' => $request->kd_wilayah,
                        'kd_komoditas' => $request->kd_komoditas,
                        'sort' => $sortColumn,
                        'direction' => $sortDirection,
                    ];
                } else {
                    $response = [
                        'inflasi' => $inflasi,
                        'message' => 'Data ditemukan.',
                        'status' => 'success',
                        'bulan' => $request->bulan,
                        'tahun' => $request->tahun,
                        'kd_level' => $request->kd_level,
                        'kd_wilayah' => $request->kd_wilayah,
                        'kd_komoditas' => $request->kd_komoditas,
                        'sort' => $sortColumn,
                        'direction' => $sortDirection,
                    ];
                }
            } else {
                Log::info('No BulanTahun found for', [
                    'bulan' => $request->bulan,
                    'tahun' => $request->tahun,
                ]);
                $response = [
                    'inflasi' => collect(),
                    'message' => 'Data tidak tersedia untuk bulan dan tahun yang dipilih.',
                    'status' => 'no_data',
                    'bulan' => $request->bulan,
                    'tahun' => $request->tahun,
                    'kd_level' => $request->kd_level,
                    'kd_wilayah' => $request->kd_wilayah,
                    'kd_komoditas' => $request->kd_komoditas,
                    'sort' => $request->input('sort', 'kd_komoditas'),
                    'direction' => $request->input('direction', 'asc'),
                ];
            }
        } else {
            Log::info('Missing required filters', $request->only(['bulan', 'tahun', 'kd_level', 'kd_wilayah']));
        }

        return view('data.edit', array_merge($response));
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

    public function hapus(Request $request)
    {
        // TODO: error stuff
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:2100',
            'level' => 'required|string|in:01,02,03,04,05',
        ]);

        try {
            $bulanTahun = BulanTahun::where('bulan', $request->bulan)
                ->where('tahun', $request->tahun)
                ->first();

            if ($bulanTahun) {
                Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
                    ->where('kd_level', $request->level)
                    ->delete();

                return redirect()->back()->with('success', 'Data deleted successfully.');
            } else {
                return redirect()->back()->with('error', 'No data found for the specified period.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting data: ' . $e->getMessage());
        }
    }

    public function findInflasiId(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:2100',
            'komoditas_id' => 'required|integer',
            'kd_wilayah' => 'required|string',
        ]);

        $bulanTahun = BulanTahun::where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->first();

        if (!$bulanTahun) {
            return response()->json(['error' => 'No data found for the specified period.'], 404);
        }

        $inflasi = Inflasi::where('bulan_tahun_id', $bulanTahun->bulan_tahun_id)
            ->where('komoditas_id', $request->komoditas_id)
            ->where('kd_wilayah', $request->kd_wilayah)
            ->first();

        if (!$inflasi) {
            return response()->json(['error' => 'No data found for the specified combination.'], 404);
        }

        return response()->json(['inflasi_id' => $inflasi->id]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'harga' => 'required|numeric',
        ]);

        try {
            $inflasi = Inflasi::findOrFail($id);
            $inflasi->harga = $request->harga;
            $inflasi->save();

            return redirect()->back()->with('success', 'Data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating data: ' . $e->getMessage());
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
            'harga' => 'required|numeric',
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
                'harga' => $request->harga,
            ]);

            return redirect()->back()->with('success', 'Data added successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error adding data: ' . $e->getMessage());
        }
    }
}
