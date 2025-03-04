<?php

use App\Http\Controllers\DataController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VisualisasiController;
use App\Http\Controllers\RekonsiliasiController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\isPusat;
use App\Models\Komoditas;
use App\Models\Wilayah;
use Illuminate\Support\Facades\Cache;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

//visualisasi
Route::get('/viz', [VisualisasiController::class, 'create'])->name('visualisasi.create');

// Data routes, protected by both auth and ispusat middleware
Route::middleware(['pusat'])->group(function () {
    Route::get('/data/edit', [DataController::class, 'edit'])->name('data.edit');
    Route::get('/data/upload', [DataController::class, 'create'])->name('data.create');
    Route::post('/data/upload', [DataController::class, 'upload'])->name('data.upload');
    Route::post('/data/hapus', [DataController::class, 'hapus'])->name('data.hapus'); // New route for hapus
    Route::post('/data/store', [DataController::class, 'store'])->name('data.store');
    Route::patch('/data/update/{id}', [DataController::class, 'update'])->name('data.update');
});

// Rekonsiliasi
Route::get('/rekonsiliasi/pemilihan', [RekonsiliasiController::class, 'pemilihan'])->name('rekon.pemilihan');
Route::get('/rekonsiliasi/progres', [RekonsiliasiController::class, 'progres'])->name('rekon.progres');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// APIs
Route::get('/api/wilayah', function () {
    Log::info('Wilayah data NOT fetched from database', ['timestamp' => now()]);
    $data = Cache::rememberForever('wilayah_data', function () {
        Log::info('Wilayah data fetched from database', ['timestamp' => now()]);
        return [
            'provinces' => Wilayah::where('flag', 2)->get(),
            'kabkots' => Wilayah::where('flag', 3)->get(),
        ];
    });

    return response()->json($data);
});

Route::get('/api/komoditas', function () {
    Log::info('Komoditas data NOT fetched from database', ['timestamp' => now()]);
    $data = Cache::rememberForever('komoditas_data', function () {
        Log::info('Komoditas data fetched from database', ['timestamp' => now()]);
        return Komoditas::all();

    });

    return response()->json($data);
});

Route::get('/api/check-username', [RegisteredUserController::class, 'checkUsername']);

Route::get('/find-inflasi-id', [DataController::class, 'findInflasiId']);



require __DIR__.'/auth.php';
