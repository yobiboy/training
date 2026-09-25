<?php

namespace Database\Seeders;

use App\Models\ProcessingStage;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The two processing stages a submitted voucher passes through: Finance
 * Processor review, then Finance Supervisor approval. Idempotent:
 * re-seeding updates the same records (matched by sequence) instead of duplicating them.
 */
class ProcessingStageSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $stages = [
            ['name' => ProcessingStage::FINANCE_PROCESSOR, 'sequence' => 1],
            ['name' => ProcessingStage::SUPERVISOR, 'sequence' => 2],
        ];

        foreach ($stages as $stage) {
            ProcessingStage::updateOrCreate(
                ['sequence' => $stage['sequence']],
                [
                    'name' => $stage['name'],
                    'is_required' => true,
                    'created_by' => $admin->id,
                ],
            );
        }
    }
}
