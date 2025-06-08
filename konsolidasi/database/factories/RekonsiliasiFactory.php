<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RekonsiliasiFactory extends Factory
{
    protected $model = \App\Models\Rekonsiliasi::class;

    public function definition()
    {
        return [
            'inflasi_id' => 1, // Will override
            'bulan_tahun_id' => 1, // Will override
            'user_id' => null,
            'alasan' => $this->faker->sentence,
            'detail' => $this->faker->paragraph,
            'media' => $this->faker->url,
        ];
    }
}
