<?php

namespace Database\Seeders;

use App\Models\Fund;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sample funds for the admin panel. Idempotent: re-seeding updates the same
 * records instead of duplicating them.
 */
class FundSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $funds = [
            ['code' => 'GF-101', 'name' => 'General Fund'],
            ['code' => 'TF-201', 'name' => 'Trust Fund'],
            ['code' => 'SEF-301', 'name' => 'Special Education Fund'],
            ['code' => 'CDF-401', 'name' => 'Countryside Development Fund'],
            ['code' => 'CDRRMF-501', 'name' => 'Calamity/Disaster Risk Reduction Management Fund'],
        ];

        foreach ($funds as $fund) {
            Fund::updateOrCreate(
                ['code' => $fund['code']],
                [
                    'name' => $fund['name'],
                    'is_active' => true,
                    'created_by' => $admin->id,
                ],
            );
        }
    }
}
