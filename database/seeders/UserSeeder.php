<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Additional sample users spread across the Requesting Unit, Finance
 * Processor, and Finance Supervisor roles and offices, so role-scoped lists
 * and dashboards have more than the single demo account per role to work
 * with. Each user logs in with the factory's default password. Idempotent:
 * only tops a role up to its target headcount instead of adding more users
 * every run.
 */
class UserSeeder extends Seeder
{
    private const USERS_PER_ROLE = 3;

    public function run(): void
    {
        $offices = Office::all();

        foreach (['requesting_unit', 'finance_processor', 'finance_supervisor'] as $role) {
            $missing = self::USERS_PER_ROLE - User::role($role)->count();

            for ($i = 0; $i < $missing; $i++) {
                $user = User::factory()->create([
                    'office_id' => $offices->isNotEmpty() ? $offices->random()->id : null,
                ]);

                $user->assignRole($role);
            }
        }
    }
}
