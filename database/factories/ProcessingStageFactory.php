<?php

namespace Database\Factories;

use App\Models\ProcessingStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessingStage>
 */
class ProcessingStageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'sequence' => $this->faker->numberBetween(1, 20),
            'is_required' => true,
            'created_by' => User::factory(),
        ];
    }
}
