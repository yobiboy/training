<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Offices and colleges typical of a Philippine state university or college
 * (SUC), plus the demo users' office assignments. Idempotent: re-seeding
 * updates the same records (matched by code) instead of duplicating them.
 */
class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $offices = [
            // Executive
            ['code' => 'OP', 'name' => 'Office of the University President'],
            ['code' => 'OVPAA', 'name' => 'Office of the Vice President for Academic Affairs'],
            ['code' => 'OVPAF', 'name' => 'Office of the Vice President for Administration and Finance'],
            ['code' => 'OVPRE', 'name' => 'Office of the Vice President for Research, Extension, and Innovation'],
            ['code' => 'OVPPD', 'name' => 'Office of the Vice President for Planning and Development'],
            ['code' => 'OUS', 'name' => 'Office of the University and Board Secretary'],

            // Finance
            ['code' => 'ACCTG', 'name' => 'Accounting Office'],
            ['code' => 'BUDGET', 'name' => 'Budget Office'],
            ['code' => 'CASH', 'name' => 'Cashiering Office'],

            // Administration and support services
            ['code' => 'HRMO', 'name' => 'Human Resource Management Office'],
            ['code' => 'SPMO', 'name' => 'Supply and Property Management Office'],
            ['code' => 'BAC', 'name' => 'Bids and Awards Committee Secretariat'],
            ['code' => 'RECORDS', 'name' => 'Records Management Office'],
            ['code' => 'PPFO', 'name' => 'Physical Plant and Facilities Office'],
            ['code' => 'ICTO', 'name' => 'Information and Communications Technology Office'],
            ['code' => 'IAS', 'name' => 'Internal Audit Service'],
            ['code' => 'LEGAL', 'name' => 'Legal Office'],
            ['code' => 'PDO', 'name' => 'Planning and Development Office'],
            ['code' => 'QAO', 'name' => 'Quality Assurance Office'],
            ['code' => 'BAO', 'name' => 'Business Affairs and Income Generating Projects Office'],

            // Academic and student services
            ['code' => 'REGISTRAR', 'name' => 'Office of the University Registrar'],
            ['code' => 'OSAS', 'name' => 'Office of Student Affairs and Services'],
            ['code' => 'LIBRARY', 'name' => 'University Library'],
            ['code' => 'GUIDANCE', 'name' => 'Guidance and Counseling Office'],
            ['code' => 'HSU', 'name' => 'Health Services Unit'],
            ['code' => 'NSTP', 'name' => 'National Service Training Program Office'],
            ['code' => 'RDO', 'name' => 'Research and Development Office'],
            ['code' => 'EXT', 'name' => 'Extension Services Office'],

            // Colleges
            ['code' => 'CAS', 'name' => 'College of Arts and Sciences'],
            ['code' => 'CBA', 'name' => 'College of Business Administration and Accountancy'],
            ['code' => 'COED', 'name' => 'College of Education'],
            ['code' => 'COE', 'name' => 'College of Engineering'],
            ['code' => 'CICS', 'name' => 'College of Information and Computing Sciences'],
            ['code' => 'CAF', 'name' => 'College of Agriculture and Forestry'],
            ['code' => 'CON', 'name' => 'College of Nursing and Allied Health Sciences'],
            ['code' => 'CCJE', 'name' => 'College of Criminal Justice Education'],
            ['code' => 'CIT', 'name' => 'College of Industrial Technology'],
            ['code' => 'CHTM', 'name' => 'College of Hospitality and Tourism Management'],
            ['code' => 'GS', 'name' => 'Graduate School'],
            ['code' => 'LHS', 'name' => 'Laboratory High School'],
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
            'admin@example.com' => 'OP',
            'requester@example.com' => 'COE',
            'processor@example.com' => 'ACCTG',
            'supervisor@example.com' => 'ACCTG',
        ];

        foreach ($assignments as $email => $officeCode) {
            $officeId = Office::where('code', $officeCode)->value('id');

            User::where('email', $email)->update(['office_id' => $officeId]);
        }
    }
}
