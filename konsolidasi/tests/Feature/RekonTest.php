<?php

use App\Models\Rekonsiliasi;
use App\Models\User;
use App\Models\Inflasi;
use App\Models\BulanTahun;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use function Pest\Stressless\stress;

//EACH TEST?? each TEST??
// uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a Wilayah record
    $wilayah = Wilayah::factory()->create([
        'kd_wilayah' => '0',
        'nama_wilayah' => 'Default Wilayah',
        'flag' => 1,
        'parent_kd' => null,
    ]);
    Log::info('Wilayah created', ['kd_wilayah' => $wilayah->kd_wilayah]);

    // Create a BulanTahun record
    $bulanTahun = BulanTahun::factory()->create([
        'bulan_tahun_id' => 1,
        'bulan' => 3,
        'tahun' => 2025,
        'aktif' => 1,
    ]);
    Log::info('BulanTahun created', ['bulan_tahun_id' => $bulanTahun->bulan_tahun_id]);

    // Create an Inflasi record
    $inflasi = Inflasi::factory()->create([
        'inflasi_id' => 1,
        'bulan_tahun_id' => $bulanTahun->bulan_tahun_id,
    ]);
    Log::info('Inflasi created', ['inflasi_id' => $inflasi->inflasi_id]);

    // Create 10 users with fixed user_id values
    $this->users = collect();
    for ($i = 1; $i <= 10; $i++) {
        $user = User::factory()->create([
            'user_id' => 100 + $i, // Fixed user_id: 101, 102, ..., 110
            'is_pusat' => 1,
            'is_admin' => 0,
            'kd_wilayah' => $wilayah->kd_wilayah,
            'password' => bcrypt('password'),
        ]);
        $this->users->push($user);

        // Create Rekonsiliasi record
        Rekonsiliasi::factory()->create([
            'rekonsiliasi_id' => 364 + ($i - 1), // 364, 365, ..., 373
            'inflasi_id' => $inflasi->inflasi_id,
            'user_id' => $user->user_id, // 101, 102, ..., 110
            'bulan_tahun_id' => $bulanTahun->bulan_tahun_id,
        ]);
        Log::info('Rekonsiliasi created', ['rekonsiliasi_id' => 364 + ($i - 1), 'user_id' => $user->user_id]);
    }
    Log::info('Users created', ['count' => $this->users->count(), 'user_ids' => $this->users->pluck('user_id')->toArray()]);

    // Assert Rekonsiliasi records exist
    $rekonsiliasiCount = Rekonsiliasi::whereBetween('rekonsiliasi_id', [364, 373])->count();
    expect($rekonsiliasiCount)->toBe(10, "Expected 10 Rekonsiliasi records, found {$rekonsiliasiCount}");

    // Log specific record for rekonsiliasi_id 364
    $rekonsiliasi364 = Rekonsiliasi::find(364);
    expect($rekonsiliasi364)->not->toBeNull("Rekonsiliasi record with ID 364 not found");
    Log::info('Rekonsiliasi 364 details', [
        'exists' => !is_null($rekonsiliasi364),
        'data' => $rekonsiliasi364 ? $rekonsiliasi364->toArray() : null,
    ]);
});

// test('can update rekonsiliasi single request', function () {
//     $user = $this->users->first(); // user_id: 101
//     $response = $this->putJson("/rekonsiliasi/update/364", [
//         'user_id' => $user->user_id, // 101
//         'alasan' => 'Gagal Panen, Promo dan Diskon',
//         'detail' => 'give me the token man',
//         'media' => null,
//     ]);
//     Log::info('Single request sent', ['rekonsiliasi_id' => 364, 'user_id' => $user->user_id]);
//     $response->assertStatus(200);
//     $this->assertDatabaseHas('rekonsiliasi', [
//         'rekonsiliasi_id' => 364,
//         'user_id' => $user->user_id,
//         'alasan' => 'Gagal Panen, Promo dan Diskon',
//     ]);
// });

// test('can handle stress sequentially for 10 users', function () {
//     $updateData = [
//         'alasan' => 'Gagal Panen, Promo dan Diskon',
//         'detail' => 'give me the token man',
//         'media' => null,
//     ];

//     $results = [];
//     foreach ($this->users as $index => $user) {
//         $rekonsiliasiId = 364 + $index; // 364, 365, ..., 373
//         $updateData['user_id'] = $user->user_id; // 101, 102, ..., 110

//         $rekonsiliasi = Rekonsiliasi::find($rekonsiliasiId);
//         expect($rekonsiliasi)->not->toBeNull("Rekonsiliasi record with ID {$rekonsiliasiId} not found before stress test");

//         try {
//             $results[$index] = stress("http://localhost:8000/rekonsiliasi/update/{$rekonsiliasiId}")
//                 ->headers([
//                     'Content-Type' => 'application/json',
//                     'Accept' => 'application/json',
//                 ])
//                 ->put($updateData)
//                 ->for(1)
//                 ->seconds();
//             Log::info('Sequential stress test sent', [
//                 'rekonsiliasi_id' => $rekonsiliasiId,
//                 'user_id' => $user->user_id,
//             ]);
//             $results[$index]->dump();
//         } catch (\Exception $e) {
//             Log::error('Sequential stress test failed', [
//                 'rekonsiliasi_id' => $rekonsiliasiId,
//                 'user_id' => $user->user_id,
//                 'error' => $e->getMessage(),
//             ]);
//             throw $e;
//         }
//     }

//     expect(Rekonsiliasi::find(364))->not->toBeNull("Rekonsiliasi 364 missing after update");
//     expect(Rekonsiliasi::find(373))->not->toBeNull("Rekonsiliasi 373 missing after update");
//     $this->assertDatabaseHas('rekonsiliasi', [
//         'rekonsiliasi_id' => 364,
//         'alasan' => 'Gagal Panen, Promo dan Diskon',
//     ]);
//     $this->assertDatabaseHas('rekonsiliasi', [
//         'rekonsiliasi_id' => 373,
//         'alasan' => 'Gagal Panen, Promo dan Diskon',
//     ]);
// });


// test('can handle stress concurrently for 10 users', function () {
//     $updateData = [
//         'alasan' => 'Gagal Panen, Promo dan Diskon',
//         'detail' => 'give me the token man',
//         'media' => null,
//     ];

//     $responses = [];
//     foreach ($this->users as $index => $user) {
//         $rekonsiliasiId = 364 + $index; // 364, 365, ..., 373
//         $updateData['user_id'] = $user->user_id; // 101, 102, ..., 110

//         // Assert record exists before test
//         $rekonsiliasi = Rekonsiliasi::find($rekonsiliasiId);
//         expect($rekonsiliasi)->not->toBeNull("Rekonsiliasi record with ID {$rekonsiliasiId} not found before stress test");

//         // Send request
//         Log::info('Users in database before request', ['users' => User::all()->pluck('user_id')->toArray()]);
//         $response = $this->putJson("/rekonsiliasi/update/{$rekonsiliasiId}", $updateData);
//         $responses[] = $response;
//         Log::info('Concurrent stress test sent', [
//             'rekonsiliasi_id' => $rekonsiliasiId,
//             'user_id' => $user->user_id,
//         ]);
//     }

//     // Assert all responses
//     foreach ($responses as $response) {
//         $response->assertStatus(200);
//     }

//     // Assert database state
//     expect(Rekonsiliasi::find(364))->not->toBeNull("Rekonsiliasi 364 missing after update");
//     expect(Rekonsiliasi::find(373))->not->toBeNull("Rekonsiliasi 373 missing after update");
//     $this->assertDatabaseHas('rekonsiliasi', [
//         'rekonsiliasi_id' => 364,
//         'alasan' => 'Gagal Panen, Promo dan Diskon',
//     ]);
//     $this->assertDatabaseHas('rekonsiliasi', [
//         'rekonsiliasi_id' => 373,
//         'alasan' => 'Gagal Panen, Promo dan Diskon',
//     ]);
// });
