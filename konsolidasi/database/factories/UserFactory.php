<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName,
            'password' => bcrypt('password'),
            'nama_lengkap' => $this->faker->name,
            'level' => $this->faker->numberBetween(0, 5),
            'kd_wilayah' => '00',
            'user_sso' => false,
        ];
    }
}
