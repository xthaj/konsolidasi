<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Inflasi;
use App\Models\Komoditas;
use App\Models\Wilayah;
use App\Models\BulanTahun;
use App\Models\LevelHarga;

class InflasiFactory extends Factory
{
    protected $model = \App\Models\Inflasi::class;

    public function definition(): array
    {
        return [
            'inflasi_id' => $this->faker->unique()->numberBetween(1, 1000), // Will be overridden in seeder
            'kd_komoditas' => $this->faker->randomElement(Komoditas::pluck('kd_komoditas')->toArray()),
            'kd_wilayah' => $this->faker->randomElement(Wilayah::pluck('kd_wilayah')->toArray()),
            'bulan_tahun_id' => BulanTahun::first()->bulan_tahun_id ?? 1, // Use existing or fallback
            'kd_level' => $this->faker->randomElement(LevelHarga::pluck('kd_level')->toArray()),
            'inflasi' => $this->faker->randomFloat(2, -10, 10),
            'andil' => $this->faker->randomFloat(4, -1, 1),
        ];
    }
}
