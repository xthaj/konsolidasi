<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WilayahFactory extends Factory
{
    protected $model = \App\Models\Wilayah::class;

    public function definition(): array
    {
        return [
            'kd_wilayah' => $this->faker->unique()->numerify('WIL#####'),
            'nama_wilayah' => $this->faker->city,
            'flag' => $this->faker->numberBetween(0, 1),
            'parent_kd' => null,
        ];
    }
}
