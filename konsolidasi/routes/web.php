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
use App\Http\Controllers\BulanTahunController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\isPusat;
use App\Models\BulanTahun;
use App\Models\Wilayah;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\InflasiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WilayahController;
use App\Http\Resources\WilayahResource;
use App\Http\Resources\BulanTahunResource;


Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');


// Data routes, protected by both auth and ispusat middleware
Route::middleware(['auth'])->group(function () {

    // User management routes
    Route::get('/users', [UserController::class, 'index'])->name('user.index');

    // Returns json
    Route::get('/user', [UserController::class, 'getUsers']);
    Route::post('/user', [UserController::class, 'store']);
    Route::put('/user/{user_id}', [UserController::class, 'update']);
    Route::delete('/user/{user_id}', [UserController::class, 'destroy']);


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
    Route::get('/api/visualisasi', [VisualisasiController::class, 'apiVisualisasi']);
    Route::post('/visualisasi/cek-data', [VisualisasiController::class, 'cekData']);
});

//Bulan tahun


// Rekonsiliasi
Route::get('/rekonsiliasi/pemilihan', [RekonsiliasiController::class, 'pemilihan'])->name('rekon.pemilihan');

Route::get('/rekonsiliasi/pembahasan', [RekonsiliasiController::class, 'pembahasan'])->name('rekon.pembahasan');
Route::get('/api/rekonsiliasi/pembahasan', [RekonsiliasiController::class, 'apiPembahasan']);
Route::patch('/api/rekonsiliasi/{id}/pembahasan', [RekonsiliasiController::class, 'updatePembahasan']);

Route::get('/rekonsiliasi/progres', [RekonsiliasiController::class, 'progres'])->name('rekon.progres');
Route::get('/rekonsiliasi/progres-skl', [RekonsiliasiController::class, 'progres_skl'])->name('rekon.progres-skl');

Route::get('/api/rekonsiliasi/progres', [RekonsiliasiController::class, 'apiProgres'])->name('api.rekon.progres');

Route::post('/rekonsiliasi/confirm', [DataController::class, 'confirmRekonsiliasi'])->name('rekonsiliasi.confirm');
Route::put('/rekonsiliasi/update/{id}', [RekonsiliasiController::class, 'update'])->name('rekonsiliasi.update');
Route::delete('/rekonsiliasi/{id}', [RekonsiliasiController::class, 'destroy'])->name('rekon.destroy');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/pengaturan', [DataController::class, 'pengaturan'])->name('pengaturan');

    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

// APIs




// master Section
// komoditas section
Route::get('/master/komoditas', [DataController::class, 'master_komoditas'])->name('master.komoditas');
Route::post('/komoditas', [KomoditasController::class, 'store']); // Add Komoditas
Route::put('/komoditas/{kd_komoditas}', [KomoditasController::class, 'update']); // Edit Komoditas
Route::delete('/komoditas/{kd_komoditas}', [KomoditasController::class, 'destroy']); // Delete Komoditas
Route::get('/all-komoditas', [KomoditasController::class, 'getAllKomoditas']); // Add Komoditas

// alasan section
Route::get('/master/alasan', [DataController::class, 'master_alasan'])->name('master.alasan');
Route::post('/alasan', [AlasanController::class, 'store']);
Route::delete('/alasan/{id}', [AlasanController::class, 'destroy']);
Route::get('/all-alasan', [AlasanController::class, 'getAllAlasan']);

// wilayah section
Route::get('/master/wilayah', [WilayahController::class, 'index'])->name('master.wilayah');

Route::get('/all-wilayah', [WilayahController::class, 'getAllWilayah']);
Route::post('/segmented-wilayah', [WilayahController::class, 'getSegmentedWilayah']);

// bulan tahun section
Route::post('/bulan-tahun', [BulanTahunController::class, 'update']);
Route::get('/bulan-tahun', [BulanTahunController::class, 'get']);

Route::get('/api/check-username', [RegisteredUserController::class, 'checkUsername']);
Route::get('/rekonsiliasi/user-provinsi', [UserController::class, 'getProvinsi'])->name('rekon.get_provinsi')->middleware('auth');

Route::post('/api/inflasi-id', [DataController::class, 'findInflasiId']);
Route::put('/api/data/inflasi/{id}', [InflasiController::class, 'update'])->name('inflasi.update');

// Unused
// master downloads
// Route::get('/komoditas/export', function () {
//     return Excel::download(new KomoditasExport, 'master_komoditas.xlsx');
// });

// Route::get('/wilayah/export', function () {
//     return Excel::download(new WilayahExport, 'master_wilayah.xlsx');
// });

// wilayah
// Route::post('/wilayah', [WilayahController::class, 'store']);
// Route::put('/wilayah/{kd_wilayah}', [WilayahController::class, 'update']);
// Route::delete('/wilayah/{kd_wilayah}', [WilayahController::class, 'destroy']);


require __DIR__ . '/auth.php';
