<?php

use App\Http\Controllers\DataController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VisualisasiController;
use App\Http\Controllers\RekonsiliasiController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\KomoditasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AlasanController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\isPusat;
use App\Models\BulanTahun;
use App\Models\Komoditas;
use App\Models\Wilayah;
use App\Models\Alasan;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\KomoditasExport;
use App\Exports\WilayahExport;
use App\Http\Controllers\AkunController;
use App\Http\Resources\AlasanResource;
use App\Http\Resources\WilayahResource;
use App\Http\Resources\BulanTahunResource;
use App\Http\Resources\KomoditasResource;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');


// Data routes, protected by both auth and ispusat middleware
Route::middleware(['auth'])->group(function () {

    Route::get('/akun', [AkunController::class, 'index'])->name('akun.index');
    Route::get('/akun', [AkunController::class, 'index'])->name('akun.index');
    Route::get('/api/users', [AkunController::class, 'getUsers']);
    Route::post('/api/users', [AkunController::class, 'store']);
    Route::put('/api/users/{user_id}', [AkunController::class, 'update']);
    Route::delete('/api/users/{user_id}', [AkunController::class, 'destroy']);


    Route::get('/data/upload', [DataController::class, 'create'])->name('data.create');
    Route::post('/data/upload', [DataController::class, 'upload'])->name('data.upload');
    Route::post('/data/final-upload', [DataController::class, 'final_upload'])->name('data.final');

    Route::post('/data/hapus', [DataController::class, 'hapus'])->name('data.hapus');
    Route::post('/data/final-hapus', [DataController::class, 'hapus_final'])->name('data.hapus.final');

    Route::post('/data/export/final', [DataController::class, 'export_final'])->name('data.export.final');

    Route::delete('/data/delete/{id}', [DataController::class, 'delete'])->name('data.delete');
    Route::post('/data/store', [DataController::class, 'store'])->name('data.store');
    Route::patch('/data/update/{id}', [DataController::class, 'update'])->name('data.update');
    Route::get('/data/edit', [DataController::class, 'edit'])->name('data.edit');
    Route::get('/api/data/edit', [DataController::class, 'apiEdit'])->name('api.data.edit');

    Route::get('/data/finalisasi', [DataController::class, 'finalisasi'])->name('data.finalisasi');


    //visualisasi
    Route::get('/visualisasi', [VisualisasiController::class, 'create'])->name('visualisasi.create');
    Route::post('/visualisasi/cek-data', [VisualisasiController::class, 'cekData']);
});

//Bulan tahun


// Rekonsiliasi
Route::get('/rekonsiliasi/pemilihan', [RekonsiliasiController::class, 'pemilihan'])->name('rekon.pemilihan');

Route::get('/rekonsiliasi/pembahasan', [RekonsiliasiController::class, 'pembahasan'])->name('rekon.pembahasan');
Route::get('/api/rekonsiliasi/pembahasan', [RekonsiliasiController::class, 'apiPembahasan']);
Route::patch('/rekonsiliasi/{id}/pembahasan', [RekonsiliasiController::class, 'updatePembahasan']);

Route::get('/rekonsiliasi/progres', [RekonsiliasiController::class, 'progres'])->name('rekon.progres');
Route::get('/api/rekonsiliasi/progres', [RekonsiliasiController::class, 'apiProgres'])->name('api.rekon.progres');

Route::post('/rekonsiliasi/confirm', [DataController::class, 'confirmRekonsiliasi'])->name('rekonsiliasi.confirm');
Route::put('/rekonsiliasi/update/{id}', [RekonsiliasiController::class, 'update'])->name('rekonsiliasi.update');
Route::delete('/rekonsiliasi/{id}', [RekonsiliasiController::class, 'destroy'])->name('rekon.destroy');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/pengaturan', [DataController::class, 'pengaturan'])->name('pengaturan');

    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    // master
    Route::get('/master/komoditas', [DataController::class, 'master_komoditas'])->name('master.komoditas');
    Route::get('/master/wilayah', [DataController::class, 'master_wilayah'])->name('master.wilayah');
    Route::get('/master/alasan', [DataController::class, 'master_alasan'])->name('master.alasan');
});

// APIs
Route::get('/api/wilayah', function () {
    Log::info('Wilayah data NOT fetched from database', ['timestamp' => now()]);

    $data = Cache::rememberForever('wilayah_data', function () {
        Log::info('Wilayah data fetched from database', ['timestamp' => now()]);
        return [
            'provinces' => WilayahResource::collection(Wilayah::where('flag', 2)->get()),
            'kabkots' => WilayahResource::collection(Wilayah::where('flag', 3)->get()),
        ];
    });

    return response()->json(['data' => $data]);
});

Route::get('/api/bulan_tahun', function () {
    Log::info('BT aktif data NOT fetched from database', ['timestamp' => now()]);

    $data = Cache::remember('bt_aktif', now()->addWeek(), function () {
        Log::info('BT aktif fetched from database', ['timestamp' => now()]);

        $btAktif = BulanTahun::where('aktif', 1)->first();
        $minTahun = BulanTahun::min('tahun') ?? now()->year;
        $maxTahun = BulanTahun::max('tahun') ?? now()->year;

        $tahun = range($minTahun - 2, $maxTahun + 2);

        return [
            'bt_aktif' => $btAktif,
            'tahun' => $tahun,
        ];
    });

    return BulanTahunResource::make($data);
});

// Komoditas
Route::post('/komoditas', [KomoditasController::class, 'store']); // Add Komoditas
Route::put('/komoditas/{kd_komoditas}', [KomoditasController::class, 'update']); // Edit Komoditas
Route::delete('/komoditas/{kd_komoditas}', [KomoditasController::class, 'destroy']); // Delete Komoditas

// NOTE: The APIs can be cleaned up further by using resource stuff

Route::get('/api/komoditas', function () {
    Log::info('Komoditas data NOT fetched from database', ['timestamp' => now()]);
    $data = Cache::rememberForever('komoditas_data', function () {
        Log::info('Komoditas data fetched from database', ['timestamp' => now()]);
        return Komoditas::all();
    });

    return KomoditasResource::collection($data);
});



Route::get('/api/alasan', function () {
    Log::info('Alasan data NOT fetched from database', ['timestamp' => now()]);
    $data = Cache::rememberForever('alasan_data', function () {
        Log::info('Alasan data fetched from database', ['timestamp' => now()]);
        return Alasan::all();
    });
    return AlasanResource::collection($data);
});


Route::get('/komoditas/export', function () {
    return Excel::download(new KomoditasExport, 'master_komoditas.xlsx');
});

Route::get('/wilayah/export', function () {
    return Excel::download(new WilayahExport, 'master_wilayah.xlsx');
});



Route::post('/update-bulan-tahun', [DataController::class, 'updateBulanTahun']);

Route::get('/api/check-username', [RegisteredUserController::class, 'checkUsername']);

Route::post('/api/inflasi-id', [DataController::class, 'findInflasiId']);

Route::post('/alasan', [AlasanController::class, 'store']);
Route::delete('/alasan/{id}', [AlasanController::class, 'destroy']);


require __DIR__ . '/auth.php';
