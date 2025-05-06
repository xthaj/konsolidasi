<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = \App\Models\User::class;

    public function definition(): array
    {
        return [
            'user_id' => $this->faker->unique()->numberBetween(1001, 2000),
            'username' => $this->faker->unique()->numerify('######'),
            'password' => bcrypt('password'),
            'nama_lengkap' => $this->faker->name,
            'is_pusat' => 1,
            'is_admin' => 1,
            'kd_wilayah' => 0,
        ];
    }
}
