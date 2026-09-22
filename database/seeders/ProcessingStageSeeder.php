<?php

namespace Database\Seeders;

use App\Models\ProcessingStage;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The two processing stages a submitted voucher passes through: Finance
 * Processor review, then Finance Supervisor approval. Idempotent:
 * re-seeding updates the same records instead of duplicating them.
 */
class ProcessingStageSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $stages = [
            ['name' => 'Finance Processing', 'sequence' => 1],
            ['name' => 'Finance Supervision', 'sequence' => 2],
        ];

        foreach ($stages as $stage) {
            ProcessingStage::updateOrCreate(
                ['name' => $stage['name']],
                [
                    'sequence' => $stage['sequence'],
                    'is_required' => true,
                    'created_by' => $admin->id,
                ],
            );
        }
    }
}
