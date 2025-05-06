<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LevelHargaFactory extends Factory
{
    protected $model = \App\Models\LevelHarga::class;

    public function definition(): array
    {
        return [
            'kd_level' => $this->faker->unique()->numerify('L#'),
            'nama_level' => $this->faker->word,
        ];
    }
}
