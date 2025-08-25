<?php

use App\Http\Controllers\DataController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VisualisasiController;
use App\Http\Controllers\RekonsiliasiController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\KomoditasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AlasanController;
use App\Http\Controllers\BulanTahunController;
use App\Http\Controllers\InflasiController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WilayahController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\isPusat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
// SSOController

// Route::get('/test', function () {
//     abort(403);
// });

Route::middleware('admin', 'pusat')->get('/clear-app-cache-23898', function () {
    Cache::flush(); // Clears all cache stored via Cache facade

    $userId = auth()->id(); // or auth()->user()->id
    Log::info("Application cache flushed by user ID: {$userId}");

    return 'Application cache cleared!';
});

Route::get('/geojson-api/provinsi', function () {
    return response()->file(public_path('geojson/provinsi.json'), [
        'Content-Type' => 'application/json',
    ]);
});

Route::get('/geojson-api/kabkot', function () {
    return response()->file(public_path('geojson/kabkot.json'), [
        'Content-Type' => 'application/json',
    ]);
});


// sso
Route::get('/sso/login', [SSOController::class, 'redirectToSSO'])->name('sso.login');
Route::get('/sso/callback', [SSOController::class, 'handleSSOCallback'])->name('sso.callback');


Route::middleware(['auth'])->group(function () {
    Route::get('/sso/logout', [SSOController::class, 'logoutSSO'])->name('sso.logout');
    Route::get('/sso/search-username', [SSOController::class, 'lookupPegawai'])->name('sso.search-username');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // resources
    Route::get('/all-alasan', [AlasanController::class, 'getAllAlasan']);
    Route::get('/all-komoditas', [KomoditasController::class, 'getAllKomoditas']);
    Route::get('/all-wilayah', [WilayahController::class, 'getAllWilayah']);

    Route::get('/rekonsiliasi/user-provinsi', [UserController::class, 'getUserWilayah'])->name('rekon.get_provinsi');
    Route::get('/bulan-tahun', [BulanTahunController::class, 'get']);

    Route::get('/segmented-wilayah', [WilayahController::class, 'getSegmentedWilayah']);
    Route::get('/inflasi-segmented-wilayah', [WilayahController::class, 'getInflasiSegmentedWilayah']);
    // tested
    Route::get('/api/rekonsiliasi/pengisian', [RekonsiliasiController::class, 'apipengisian']);
    Route::put('/rekonsiliasi/update/{id}', [RekonsiliasiController::class, 'update'])->name('rekonsiliasi.update');
});

Route::middleware('auth')->group(function () {
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

Route::middleware(['admin'])->group(function () {
    // User management routes
    Route::get('/users', [UserController::class, 'index'])->name('user.index');
    Route::get('/user', [UserController::class, 'getUsers']);
    Route::post('/user', [UserController::class, 'store']);
    Route::put('/user/{user_id}', [UserController::class, 'edit']);
    Route::delete('/user/{user_id}', [UserController::class, 'destroy']);
    Route::get('/api/check-username', [RegisteredUserController::class, 'checkUsername']);
});

Route::middleware(['pusat'])->group(function () {
    // upload
    Route::get('/data/upload', [InflasiController::class, 'create'])->name('data.create');
    Route::post('/data/upload', [InflasiController::class, 'upload'])->name('data.upload');
    Route::post('/data/final-upload', [InflasiController::class, 'final_upload'])->name('data.final');
    // hapus data 1 bulan 1 level
    Route::post('/data/hapus', [InflasiController::class, 'hapus'])->name('data.hapus');
    // export
    Route::post('/data/export/final', [InflasiController::class, 'export_final'])->name('data.export.final');

    // ui, single inflasi
    Route::delete('/data/delete/{id}', [InflasiController::class, 'delete']);
    Route::patch('/data/update/{id}', [InflasiController::class, 'update']);

    // rekon
    Route::get('/rekonsiliasi/pengisian', [RekonsiliasiController::class, 'pengisian'])->name('rekon.pengisian');


    // Route::post('/data/store', [DataController::class, 'store'])->name('data.store');
    Route::get('/data/edit', [InflasiController::class, 'edit'])->name('data.edit');
    Route::get('/api/data/edit', [InflasiController::class, 'fetchEditData']);

    Route::get('/data/finalisasi', [InflasiController::class, 'finalisasi'])->name('data.finalisasi');

    //visualisasi
    Route::get('/visualisasi', [VisualisasiController::class, 'create'])->name('visualisasi.create');
    Route::get('/api/visualisasi', [VisualisasiController::class, 'fetchVisualisasiData']);

    // master Section
    // komoditas section
    Route::get('/master/komoditas', [KomoditasController::class, 'index'])->name('master.komoditas');
    Route::post('/komoditas', [KomoditasController::class, 'store']); // Add Komoditas
    Route::put('/komoditas/{kd_komoditas}', [KomoditasController::class, 'update']); // Edit Komoditas
    Route::delete('/komoditas/{kd_komoditas}', [KomoditasController::class, 'destroy']); // Delete Komoditas


    // alasan section
    Route::get('/master/alasan', [AlasanController::class, 'index'])->name('master.alasan');
    Route::post('/alasan', [AlasanController::class, 'store']);
    Route::delete('/alasan/{id}', [AlasanController::class, 'destroy']);

    // wilayah section
    Route::get('/master/wilayah', [WilayahController::class, 'index'])->name('master.wilayah');
    Route::put('/wilayah/{kd_wilayah}', [WilayahController::class, 'update']); // Edit Wilayah
    // Route::patch('/wilayah/{kd_wilayah}/tracking', [WilayahController::class, 'updateTrackedInflasi']);

    // bulan tahun section
    Route::post('/bulan-tahun', [BulanTahunController::class, 'update']);

    Route::post('/api/inflasi-id', [InflasiController::class, 'findInflasiId']);
    Route::put('/api/data/inflasi/{id}', [InflasiController::class, 'update'])->name('inflasi.update');
});

Route::middleware('provinsi_or_kabkot')->group(function () {
    Route::get('/rekonsiliasi/pengisian-skl', [RekonsiliasiController::class, 'pengisian_skl'])->name('rekon.pengisian-skl');
});

Route::middleware('auth')->group(function () {
    // Rekonsiliasi
    Route::get('/rekonsiliasi/pemilihan', [RekonsiliasiController::class, 'pemilihan'])->name('rekon.pemilihan');
    Route::get('/rekonsiliasi/pembahasan', [RekonsiliasiController::class, 'pembahasan'])->name('rekon.pembahasan');
    Route::get('/rekonsiliasi/laporan', [RekonsiliasiController::class, 'laporan'])->name('rekon.laporan');

    // export
    Route::post('/data/export/rekonsiliasi', [InflasiController::class, 'export_rekonsiliasi'])->name('data.export.rekonsiliasi');

    Route::get('/api/rekonsiliasi/pembahasan', [RekonsiliasiController::class, 'fetchPembahasanData']);
    Route::patch('/api/rekonsiliasi/{id}/pembahasan', [RekonsiliasiController::class, 'updatePembahasan']);

    // Route::get('/api/rekonsiliasi/pengisian', [RekonsiliasiController::class, 'apipengisian']);

    Route::post('/rekonsiliasi/confirm', [RekonsiliasiController::class, 'confirmRekonsiliasi'])->name('rekonsiliasi.confirm');
    // Route::put('/rekonsiliasi/update/{id}', [RekonsiliasiController::class, 'update'])->name('rekonsiliasi.update');
    Route::delete('/rekonsiliasi/{id}', [RekonsiliasiController::class, 'destroy'])->name('rekon.destroy');

    // so far isi pengaturan hanyalah bulantahun
    Route::get('/pengaturan', [BulanTahunController::class, 'pengaturan'])->name('pengaturan');
});


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

// Route::post('/data/final-hapus', [DataController::class, 'hapus_final'])->name('data.hapus.final');

require __DIR__ . '/auth.php';
