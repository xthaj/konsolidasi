<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class KomoditasFactory extends Factory
{
    protected $model = \App\Models\Komoditas::class;

    public function definition(): array
    {
        return [
            'kd_komoditas' => $this->faker->unique()->numerify('K##'),
            'nama_komoditas' => $this->faker->word,
        ];
    }
}
