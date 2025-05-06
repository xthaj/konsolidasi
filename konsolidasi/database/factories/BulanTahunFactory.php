<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BulanTahunFactory extends Factory
{
    protected $model = \App\Models\BulanTahun::class;

    public function definition(): array
    {
        return [
            'bulan_tahun_id' => $this->faker->unique()->numberBetween(1, 1000),
            'bulan' => $this->faker->numberBetween(1, 12),
            'tahun' => $this->faker->numberBetween(2020, 2025),
            'aktif' => $this->faker->numberBetween(0, 1),
        ];
    }
}
