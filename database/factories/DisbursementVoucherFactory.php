<?php

namespace Database\Factories;

use App\Models\DisbursementVoucher;
use App\Models\Fund;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisbursementVoucher>
 */
class DisbursementVoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dv_no' => strtoupper($this->faker->unique()->bothify('DV-####-####')),
            'payee_name' => $this->faker->name(),
            'fund_id' => Fund::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 100000),
            'particulars' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['submitted', 'in_process', 'returned', 'for_payment', 'completed']),
            'created_by' => User::factory(),
        ];
    }
}
