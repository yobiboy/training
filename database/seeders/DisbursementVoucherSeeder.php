<?php

namespace Database\Seeders;

use App\Models\DisbursementVoucher;
use App\Models\Fund;
use App\Models\Office;
use App\Models\ProcessingStage;
use App\Models\RoutingHistory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Sample disbursement vouchers across offices and every workflow status, each
 * with a routing history that matches how it got there. Also creates one dummy
 * requesting_unit account per sample office, and gives the demo requester a
 * voucher in every status. Runs after the office, fund, stage, and demo user
 * seeders. Skips itself once the dummy accounts already have vouchers, so
 * re-seeding does not pile up duplicates.
 */
class DisbursementVoucherSeeder extends Seeder
{
    /**
     * Dummy requesting_unit accounts: email => office code.
     *
     * @var array<string, string>
     */
    private const DUMMY_REQUESTERS = [
        'coe.staff@example.com' => 'COE',
        'cas.staff@example.com' => 'CAS',
        'cba.staff@example.com' => 'CBA',
        'coed.staff@example.com' => 'COED',
        'cics.staff@example.com' => 'CICS',
        'caf.staff@example.com' => 'CAF',
        'con.staff@example.com' => 'CON',
        'spmo.staff@example.com' => 'SPMO',
        'library.staff@example.com' => 'LIBRARY',
        'osas.staff@example.com' => 'OSAS',
        'rdo.staff@example.com' => 'RDO',
    ];

    /**
     * The routing steps each scenario went through, in order. "resubmitted"
     * ends up back in the processor's queue after being returned once.
     *
     * @var array<string, list<string>>
     */
    private const SCENARIOS = [
        'draft' => [],
        'submitted' => ['submitted'],
        'returned' => ['submitted', 'returned'],
        'resubmitted' => ['submitted', 'returned', 'resubmitted'],
        'for_payment' => ['submitted', 'forwarded'],
        'completed' => ['submitted', 'forwarded', 'completed'],
    ];

    /**
     * @var list<array{payee: string, particulars: string, min: int, max: int}>
     */
    private const EXPENSES = [
        ['payee' => 'Mindanao Office Solutions, Inc.', 'particulars' => 'Payment for office supplies for the 1st semester, AY 2026–2027', 'min' => 8000, 'max' => 45000],
        ['payee' => 'TechHub Computer Center', 'particulars' => 'Procurement of desktop computers for the computer laboratory', 'min' => 150000, 'max' => 600000],
        ['payee' => 'Lakbay Travel and Tours', 'particulars' => 'Airfare for faculty presenters at the national research conference', 'min' => 12000, 'max' => 60000],
        ['payee' => 'Kusina ni Aling Nena Catering', 'particulars' => 'Catering services for the AACCUP accreditation visit', 'min' => 15000, 'max' => 50000],
        ['payee' => 'Visayan Electric Cooperative', 'particulars' => 'Payment of electricity bill for the month', 'min' => 80000, 'max' => 350000],
        ['payee' => 'CleanPro Janitorial Services', 'particulars' => 'Janitorial and maintenance services for the month', 'min' => 60000, 'max' => 180000],
        ['payee' => 'SafeGuard Security Agency', 'particulars' => 'Security guard services for the month', 'min' => 90000, 'max' => 250000],
        ['payee' => 'LabTech Scientific Supply', 'particulars' => 'Purchase of laboratory reagents and consumables', 'min' => 20000, 'max' => 120000],
        ['payee' => 'Rex Book Store', 'particulars' => 'Acquisition of library books and references', 'min' => 25000, 'max' => 150000],
        ['payee' => 'Juan dela Cruz', 'particulars' => 'Honorarium for resource speaker, faculty development seminar', 'min' => 5000, 'max' => 15000],
        ['payee' => 'Maria Santos', 'particulars' => 'Reimbursement of travel expenses to the CHED Regional Office', 'min' => 2500, 'max' => 9000],
        ['payee' => 'Pedro Reyes', 'particulars' => 'Honoraria for thesis panel members, 2nd semester', 'min' => 6000, 'max' => 24000],
        ['payee' => 'PrintMaster Digital Printing', 'particulars' => 'Printing of student handbooks and enrollment forms', 'min' => 18000, 'max' => 70000],
        ['payee' => 'AgriSupply Trading', 'particulars' => 'Purchase of seedlings and fertilizer for the demonstration farm', 'min' => 10000, 'max' => 55000],
        ['payee' => 'MedLine Pharmacy', 'particulars' => 'Replenishment of medicines for the university clinic', 'min' => 8000, 'max' => 40000],
        ['payee' => 'BuildRight Construction', 'particulars' => 'Repair of classroom ceilings and roofing, Engineering building', 'min' => 200000, 'max' => 900000],
        ['payee' => 'SportsZone Trading', 'particulars' => 'Sports equipment for the intramurals', 'min' => 15000, 'max' => 80000],
        ['payee' => 'Student Council Treasurer', 'particulars' => 'Release of student development fund for the leadership training', 'min' => 20000, 'max' => 60000],
    ];

    /**
     * @var list<string>
     */
    private const RETURN_REMARKS = [
        'Missing supporting documents: official receipts and certificate of acceptance.',
        'Obligation Request and Status (ORS) not attached.',
        'Amount does not match the approved purchase order.',
        'Charged to the wrong fund. Please revise.',
        'Payee TIN is missing from the voucher.',
        'Particulars are incomplete. Specify the period covered.',
    ];

    public function run(): void
    {
        $requester = User::where('email', 'requester@example.com')->firstOrFail();
        $processor = User::where('email', 'processor@example.com')->firstOrFail();
        $supervisor = User::where('email', 'supervisor@example.com')->firstOrFail();
        $dummyRequesters = Model::unguarded(fn (): Collection => $this->createDummyRequesters());

        if (DisbursementVoucher::whereIn('created_by', $dummyRequesters->pluck('id'))->exists()) {
            return;
        }

        $stages = [
            'processor' => ProcessingStage::where('name', ProcessingStage::FINANCE_PROCESSOR)->firstOrFail(),
            'supervisor' => ProcessingStage::where('name', ProcessingStage::SUPERVISOR)->firstOrFail(),
        ];
        $actors = ['processor' => $processor, 'supervisor' => $supervisor];
        $fundIds = Fund::where('is_active', true)->pluck('id');

        Model::unguarded(function () use ($requester, $dummyRequesters, $stages, $actors, $fundIds) {
            // The demo requester gets one voucher in every scenario, plus a
            // couple extra, so every state is visible from that account.
            foreach ([...array_keys(self::SCENARIOS), 'submitted', 'completed'] as $scenario) {
                $this->createVoucher($requester, $scenario, $stages, $actors, $fundIds->random());
            }

            foreach ($dummyRequesters as $dummyRequester) {
                foreach (array_rand(self::SCENARIOS, fake()->numberBetween(2, 4)) as $scenario) {
                    $this->createVoucher($dummyRequester, $scenario, $stages, $actors, $fundIds->random());
                }
            }
        });
    }

    /**
     * @return Collection<int, User>
     */
    private function createDummyRequesters(): Collection
    {
        $password = Hash::make(env('DEMO_USER_PASSWORD', 'password'));

        return collect(self::DUMMY_REQUESTERS)->map(function (string $officeCode, string $email) use ($password) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => fake()->name(),
                    'password' => $password,
                    'email_verified_at' => now(),
                    'office_id' => Office::where('code', $officeCode)->value('id'),
                ],
            );

            $user->syncRoles(['requesting_unit']);

            return $user;
        })->values();
    }

    /**
     * @param  array{processor: ProcessingStage, supervisor: ProcessingStage}  $stages
     * @param  array{processor: User, supervisor: User}  $actors
     */
    private function createVoucher(User $creator, string $scenario, array $stages, array $actors, int $fundId): void
    {
        $expense = fake()->randomElement(self::EXPENSES);
        $createdAt = now()->subDays(fake()->numberBetween(5, 75))->setTime(fake()->numberBetween(8, 16), fake()->numberBetween(0, 59));

        $voucher = DisbursementVoucher::create([
            'dv_no' => DisbursementVoucher::generateNextDvNo(),
            'payee_name' => $expense['payee'],
            'fund_id' => $fundId,
            'amount' => fake()->numberBetween($expense['min'], $expense['max']),
            'particulars' => $expense['particulars'],
            'status' => 'draft',
            'created_by' => $creator->id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $actedAt = $createdAt;
        $lastActor = $creator;
        $submittedAt = null;
        $completedAt = null;

        foreach (self::SCENARIOS[$scenario] as $action) {
            $actedAt = $this->nextStepTime($actedAt);

            [$stage, $actor] = match ($action) {
                'submitted', 'resubmitted' => [$stages['processor'], $creator],
                'returned' => [$stages['processor'], $actors['processor']],
                'forwarded' => [$stages['supervisor'], $actors['processor']],
                'completed' => [$stages['supervisor'], $actors['supervisor']],
            };

            RoutingHistory::create([
                'disbursement_voucher_id' => $voucher->id,
                'processing_stage_id' => $stage->id,
                'action' => $action,
                'remarks' => $action === 'returned' ? fake()->randomElement(self::RETURN_REMARKS) : null,
                'acted_by' => $actor->id,
                'acted_at' => $actedAt,
            ]);

            $lastActor = $actor;
            $submittedAt = in_array($action, ['submitted', 'resubmitted'], true) ? $actedAt : $submittedAt;
            $completedAt = $action === 'completed' ? $actedAt : null;
        }

        $voucher->update([
            'status' => $scenario === 'resubmitted' ? 'submitted' : $scenario,
            'current_stage_id' => match ($scenario) {
                'submitted', 'resubmitted' => $stages['processor']->id,
                'for_payment', 'completed' => $stages['supervisor']->id,
                default => null,
            },
            'submitted_at' => $submittedAt,
            'completed_at' => $completedAt,
            'updated_by' => $scenario === 'draft' ? null : $lastActor->id,
            'updated_at' => $actedAt,
        ]);
    }

    /**
     * A plausible time for the next routing step: a few hours to a few days
     * later, but never in the future.
     */
    private function nextStepTime(CarbonInterface $previous): CarbonInterface
    {
        $next = $previous->copy()->addHours(fake()->numberBetween(3, 96));

        return $next->isFuture() ? now() : $next;
    }
}
