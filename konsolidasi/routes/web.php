<?php

use App\Http\Controllers\AlasanController;
use App\Http\Controllers\BulanTahunController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HargaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\InflasiController;
use App\Http\Controllers\KomoditasController;
use App\Http\Controllers\RekonsiliasiController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisualisasiController;
use App\Http\Controllers\WilayahController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// Admin — flush cache aplikasi
Route::middleware('admin', 'pusat')->get('/clear-app-cache-23898', function () {
    Cache::flush();
    Log::info("Application cache flushed by user ID: " . auth()->id());
    return 'Application cache cleared!';
});

// GeoJSON — data peta untuk chart
Route::get('/geojson-api/provinsi', function () {
    return response()->file(public_path('geojson/provinsi.json'), ['Content-Type' => 'application/json']);
});
Route::get('/geojson-api/kabkot', function () {
    return response()->file(public_path('geojson/kabkot.json'), ['Content-Type' => 'application/json']);
});

// SSO — login & callback
Route::get('/sso/login', [SSOController::class, 'redirectToSSO'])->name('sso.login');
Route::get('/sso/callback', [SSOController::class, 'handleSSOCallback'])->name('sso.callback');

Route::middleware(['auth'])->group(function () {
    Route::get('/sso/logout', [SSOController::class, 'logoutSSO'])->name('sso.logout');
    Route::get('/sso/search-username', [SSOController::class, 'lookupPegawai'])->name('sso.search-username');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Harmonisasi
    Route::get('/visualisasi', [VisualisasiController::class, 'create'])->name('visualisasi.create');
    Route::get('/api/visualisasi', [VisualisasiController::class, 'fetchVisualisasiData']);

    // Rekonsiliasi
    Route::get('/rekonsiliasi/laporan', [RekonsiliasiController::class, 'laporan'])->name('rekon.laporan');
    Route::get('/api/rekonsiliasi/pengisian', [RekonsiliasiController::class, 'apipengisian']);
    Route::put('/rekonsiliasi/update/{id}', [RekonsiliasiController::class, 'update'])->name('rekonsiliasi.update');
    Route::post('/data/export/rekonsiliasi', [InflasiController::class, 'export_rekonsiliasi'])->name('data.export.rekonsiliasi');

    // Dropdown options & lookup resources
    Route::get('/all-alasan', [AlasanController::class, 'getAllAlasan']);
    Route::get('/all-komoditas', [KomoditasController::class, 'getAllKomoditas']);
    Route::get('/all-komoditas-excl', [KomoditasController::class, 'getAllKomoditasTanpaUmum']);
    Route::get('/all-komoditas-harga', [KomoditasController::class, 'getAllKomoditasHarga']);
    Route::get('/all-komoditas-with-flag', [KomoditasController::class, 'getAllKomoditasWithFlag']);
    Route::get('/all-wilayah', [WilayahController::class, 'getAllWilayah']);
    Route::get('/bulan-tahun', [BulanTahunController::class, 'get']);
    Route::get('/rekonsiliasi/user-provinsi', [UserController::class, 'getUserWilayah'])->name('rekon.get_provinsi');
    Route::get('/segmented-wilayah', [WilayahController::class, 'getSegmentedWilayah']);
    Route::get('/inflasi-segmented-wilayah', [WilayahController::class, 'getInflasiSegmentedWilayah']);

    // Profile
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

Route::middleware(['admin'])->group(function () {
    // User management — CRUD akun pengguna
    Route::get('/users', [UserController::class, 'index'])->name('user.index'); // halaman daftar user
    Route::get('/user', [UserController::class, 'getUsers']); // API data table user
    Route::post('/user', [UserController::class, 'store']); // tambah user baru
    Route::put('/user/{user_id}', [UserController::class, 'edit']); // edit user
    Route::delete('/user/{user_id}', [UserController::class, 'destroy']); // hapus user
    Route::get('/api/check-username', [RegisteredUserController::class, 'checkUsername']); // cek ketersediaan username
});

Route::middleware(['pusat'])->group(function () {
    // Upload harmonisasi & final
    Route::get('/data/upload', [InflasiController::class, 'create'])->name('data.create'); // halaman upload
    Route::post('/data/upload', [InflasiController::class, 'upload'])->name('data.upload'); // import excel harmonisasi
    Route::post('/data/final-upload', [InflasiController::class, 'final_upload'])->name('data.final'); // import final

    // Hapus data per periode
    Route::post('/data/hapus', [InflasiController::class, 'hapus'])->name('data.hapus'); // hapus harmonisasi 1 bulan 1 level
    Route::post('/data/hapus-harga', [InflasiController::class, 'hapus_harga'])->name('data.hapus-harga'); // hapus harga

    // Export
    Route::post('/data/export/final', [InflasiController::class, 'export_final'])->name('data.export.final'); // export excel final

    // Single-row inflasi CRUD (dari halaman edit)
    Route::delete('/data/delete/{id}', [InflasiController::class, 'delete']); // hapus satu baris data
    Route::patch('/data/update/{id}', [InflasiController::class, 'update']); // update satu baris data

    // Edit data (halaman & API)
    Route::get('/data/edit', [InflasiController::class, 'edit'])->name('data.edit'); // halaman edit
    Route::get('/api/data/edit', [InflasiController::class, 'fetchEditData']); // API table edit

    // Finalisasi
    Route::get('/data/finalisasi', [InflasiController::class, 'finalisasi'])->name('data.finalisasi'); // halaman finalisasi

    // Rekonsiliasi (pusat)
    Route::get('/rekonsiliasi/pengisian', [RekonsiliasiController::class, 'pengisian'])->name('rekon.pengisian'); // halaman pengisian
    Route::get('/rekonsiliasi/pemilihan', [RekonsiliasiController::class, 'pemilihan'])->name('rekon.pemilihan'); // halaman pemilihan
    Route::post('/rekonsiliasi/bulk-delete', [RekonsiliasiController::class, 'bulkDestroy'])->name('rekon.bulk-destroy'); // hapus massal
    Route::delete('/rekonsiliasi/{id}', [RekonsiliasiController::class, 'destroy'])->name('rekon.destroy'); // hapus satu rekon
    Route::post('/rekonsiliasi/confirm', [RekonsiliasiController::class, 'confirmRekonsiliasi'])->name('rekonsiliasi.confirm'); // konfirmasi batch
    Route::patch('/api/rekonsiliasi/{id}/pembahasan', [RekonsiliasiController::class, 'updatePembahasan']); // toggle pembahasan
    Route::get('/rekonsiliasi/pembahasan', [RekonsiliasiController::class, 'pembahasan'])->name('rekon.pembahasan'); // halaman pembahasan
    Route::get('/api/rekonsiliasi/pembahasan', [RekonsiliasiController::class, 'fetchPembahasanData']); // API table pembahasan

    // Master komoditas
    Route::get('/master/komoditas', [KomoditasController::class, 'index'])->name('master.komoditas'); // halaman daftar
    Route::post('/komoditas', [KomoditasController::class, 'store']); // tambah
    Route::put('/komoditas/{kd_komoditas}', [KomoditasController::class, 'update']); // edit
    Route::delete('/komoditas/{kd_komoditas}', [KomoditasController::class, 'destroy']); // hapus

    // Master alasan
    Route::get('/master/alasan', [AlasanController::class, 'index'])->name('master.alasan'); // halaman daftar
    Route::post('/alasan', [AlasanController::class, 'store']); // tambah
    Route::delete('/alasan/{id}', [AlasanController::class, 'destroy']); // hapus

    // Master wilayah
    Route::get('/master/wilayah', [WilayahController::class, 'index'])->name('master.wilayah'); // halaman daftar
    Route::put('/wilayah/{kd_wilayah}', [WilayahController::class, 'update']); // edit wilayah

    // Bulan tahun — set periode aktif
    Route::post('/bulan-tahun', [BulanTahunController::class, 'update']); // ganti periode aktif

    // Harga
    Route::get('/harga', [HargaController::class, 'create'])->name('harga.create'); // halaman harga
    Route::get('/api/harga', [HargaController::class, 'fetchHargaData']); // API chart data
    Route::post('/data/upload-harga', [InflasiController::class, 'upload_harga'])->name('data.upload-harga'); // import excel harga

    // Komoditas Harga — toggle mana yang tampil di halaman harga
    Route::get('/pengaturan/komoditas-harga', [KomoditasController::class, 'hargaIndex'])->name('pengaturan.komoditas-harga'); // halaman
    Route::patch('/komoditas/{kd_komoditas}/harga', [KomoditasController::class, 'toggleHarga']); // toggle is_harga

    // Inflasi lookup & update
    Route::post('/api/inflasi-id', [InflasiController::class, 'findInflasiId']); // cari inflasi_id
    Route::put('/api/data/inflasi/{id}', [InflasiController::class, 'update'])->name('inflasi.update'); // update nilai inflasi

    // Pengaturan periode aktif
    Route::get('/pengaturan', [BulanTahunController::class, 'pengaturan'])->name('pengaturan'); // halaman pengaturan
});

Route::middleware('provinsi_or_kabkot')->group(function () {
    // Pengisian rekonsiliasi untuk user provinsi/kabkot
    Route::get('/rekonsiliasi/pengisian-skl', [RekonsiliasiController::class, 'pengisian_skl'])->name('rekon.pengisian-skl');
});

require __DIR__ . '/auth.php';
