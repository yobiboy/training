<?php

namespace Database\Seeders;

use App\Models\Fund;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Funds a Philippine state university or college (SUC) disburses from,
 * following the fund clusters of COA Circular No. 2013-002 plus the common
 * SUC sub-funds under RA 8292. Idempotent: re-seeding updates the same
 * records (matched by code) instead of duplicating them.
 */
class FundSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $funds = [
            // Fund Cluster 01: appropriations from the General Appropriations Act
            ['code' => 'FC01-RAF', 'name' => 'Regular Agency Fund (GAA)'],
            ['code' => 'FC01-PS', 'name' => 'Regular Agency Fund – Personnel Services'],
            ['code' => 'FC01-MOOE', 'name' => 'Regular Agency Fund – Maintenance and Other Operating Expenses'],
            ['code' => 'FC01-CO', 'name' => 'Regular Agency Fund – Capital Outlay'],

            // Fund Cluster 02–04: loans, grants, and special accounts
            ['code' => 'FC02-FAP', 'name' => 'Foreign Assisted Projects Fund'],
            ['code' => 'FC03-SALF', 'name' => 'Special Account – Locally Funded / Domestic Grants'],

            // Fund Cluster 05: internally generated funds retained under RA 8292
            ['code' => 'FC05-STF', 'name' => 'Special Trust Fund – Tuition and Other School Fees'],
            ['code' => 'FC05-RF', 'name' => 'Revolving Fund'],

            // Fund Cluster 06: income generating projects
            ['code' => 'FC06-IGP', 'name' => 'Business Related Fund – Income Generating Projects'],

            // Fund Cluster 07: funds held in trust
            ['code' => 'FC07-TR', 'name' => 'Trust Receipts – Inter-Agency Transferred Funds'],
            ['code' => 'FC07-SCH', 'name' => 'Trust Receipts – Scholarship Funds (CHED / UniFAST)'],
            ['code' => 'FC07-FHE', 'name' => 'Trust Receipts – Free Higher Education (RA 10931)'],
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
