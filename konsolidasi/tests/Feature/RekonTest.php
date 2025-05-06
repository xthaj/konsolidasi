<?php

use App\Models\Rekonsiliasi;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use function Pest\Stressless\stress;

test('10 users can update different rekonsiliasi records simultaneously', function () {
    // Ensure we have at least 10 users and rekonsiliasi records available
    $requiredCount = 10;
    $users = User::whereBetween('user_id', [101, 500])->limit($requiredCount)->get();
    $rekonsiliasiRecords = Rekonsiliasi::whereBetween('rekonsiliasi_id', [364, 763])
        ->limit($requiredCount)
        ->get();

    // Verify we have enough test data
    if ($users->count() < $requiredCount || $rekonsiliasiRecords->count() < $requiredCount) {
        $this->artisan('db:seed', ['--class' => 'RekonSeeder']);
        $users = User::whereBetween('user_id', [101, 500])->limit($requiredCount)->get();
        $rekonsiliasiRecords = Rekonsiliasi::whereBetween('rekonsiliasi_id', [364, 763])
            ->limit($requiredCount)
            ->get();
    }

    expect($users)->toHaveCount($requiredCount, "Need at least {$requiredCount} users for test");
    expect($rekonsiliasiRecords)->toHaveCount($requiredCount, "Need at least {$requiredCount} rekonsiliasi records for test");

    // Prepare the test - we'll create 10 different endpoints to hit
    $endpoints = [];
    $updateData = [];

    foreach ($users as $index => $user) {
        $rekonsiliasiId = $rekonsiliasiRecords[$index]->rekonsiliasi_id;

        $endpoints[] = "/api/rekonsiliasi/update/{$rekonsiliasiId}";

        $updateData[$rekonsiliasiId] = [
            'user_id' => $user->user_id,
            'alasan' => 'Lainnya',
            'detail' => 'Concurrent update test' . ($index + 1),
            'media' => null,
        ];
    }

    // Run stress test on all endpoints simultaneously
    $results = [];
    foreach ($endpoints as $index => $endpoint) {
        $rekonsiliasiId = $rekonsiliasiRecords[$index]->rekonsiliasi_id;

        $results[$rekonsiliasiId] = stress("http://localhost:8000{$endpoint}")
            ->headers([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->put($updateData[$rekonsiliasiId])
            ->concurrently(1) // 1 concurrent request per endpoint
            ->for(5) // 5 seconds
            ->seconds();
    }

    // Assert all requests succeeded
    foreach ($results as $rekonsiliasiId => $result) {
        Log::info("Stress test results for rekonsiliasi_id {$rekonsiliasiId}", [
            'total_requests' => $result->requests()->count(),
            'failed_requests' => $result->requests()->failed()->count(),
            'median_duration_ms' => $result->requests()->duration()->med(),
        ]);

        expect($result->requests()->failed()->count())->toBe(0, "Requests failed for rekonsiliasi_id {$rekonsiliasiId}");
        expect($result->requests()->duration()->med())->toBeLessThan(500, "Response time too high for rekonsiliasi_id {$rekonsiliasiId}");
    }

    // Verify database updates
    foreach ($rekonsiliasiRecords as $index => $record) {
        $rekonsiliasiId = $record->rekonsiliasi_id;
        $userId = $users[$index]->user_id;

        $updatedRecord = Rekonsiliasi::find($rekonsiliasiId);
        expect($updatedRecord)->not->toBeNull();
        expect($updatedRecord->user_id)->toBe($userId);
        expect($updatedRecord->alasan)->toBe('Stress test update ' . ($index + 1));
    }
});
