<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RekonsiliasiFactory extends Factory
{
    protected $model = \App\Models\Rekonsiliasi::class;

    public function definition(): array
    {
        return [
            'rekonsiliasi_id' => $this->faker->unique()->numberBetween(1000, 2000), // Avoid 364–763
            'inflasi_id' => null, // Let seeder override
            'user_id' => null, // Let seeder override
            'bulan_tahun_id' => null, // Let seeder override
            'terakhir_diedit' => now(),
            'alasan' => 'Lainnya',
            'detail' => $this->faker->paragraph,
            'media' => $this->faker->url,
        ];
    }
}
