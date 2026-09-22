<?php

namespace Database\Factories;

use App\Models\DisbursementVoucher;
use App\Models\ProcessingStage;
use App\Models\RoutingHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoutingHistory>
 */
class RoutingHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disbursement_voucher_id' => DisbursementVoucher::factory(),
            'processing_stage_id' => ProcessingStage::factory(),
            'action' => $this->faker->randomElement(['received', 'processed', 'returned', 'forwarded']),
            'remarks' => $this->faker->optional()->sentence(),
            'acted_by' => User::factory(),
            'acted_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}
