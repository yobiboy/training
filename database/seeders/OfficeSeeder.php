<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sample offices and their assigned users. Idempotent: re-seeding updates
 * the same records instead of duplicating them.
 */
class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $offices = [
            ['code' => 'OMA-001', 'name' => 'Office of the Mayor'],
            ['code' => 'ACC-002', 'name' => 'Accounting Office'],
            ['code' => 'BUD-003', 'name' => 'Budget Office'],
            ['code' => 'TRE-004', 'name' => "Treasurer's Office"],
            ['code' => 'GSO-005', 'name' => 'General Services Office'],
        ];

        foreach ($offices as $office) {
            Office::updateOrCreate(
                ['code' => $office['code']],
                [
                    'name' => $office['name'],
                    'is_active' => true,
                    'created_by' => $admin->id,
                ],
            );
        }

        $assignments = [
            'admin@example.com' => 'OMA-001',
            'requester@example.com' => 'GSO-005',
            'processor@example.com' => 'GSO-005',
            'supervisor@example.com' => 'ACC-002',
        ];

        foreach ($assignments as $email => $officeCode) {
            $officeId = Office::where('code', $officeCode)->value('id');

            User::where('email', $email)->update(['office_id' => $officeId]);
        }
    }
}
