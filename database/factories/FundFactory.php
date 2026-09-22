<?php

namespace Database\Factories;

use App\Models\Fund;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fund>
 */
class FundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('FND-####')),
            'name' => $this->faker->words(3, true),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
