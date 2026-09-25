<?php

use App\Models\DisbursementVoucher;
use App\Models\Office;
use App\Models\RoutingHistory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DisbursementVoucherSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('gives the demo requester a voucher in every status', function () {
    $requester = User::where('email', 'requester@example.com')->sole();

    expect(DisbursementVoucher::where('created_by', $requester->id)->distinct()->pluck('status')->sort()->values()->all())
        ->toBe(['completed', 'draft', 'for_payment', 'returned', 'submitted']);
});

it('spreads vouchers across several offices through dummy requesting_unit accounts', function () {
    $officeIds = DisbursementVoucher::query()
        ->join('users', 'users.id', '=', 'disbursement_vouchers.created_by')
        ->distinct()
        ->pluck('users.office_id');

    expect($officeIds->count())->toBeGreaterThan(5)
        ->and(User::role('requesting_unit')->where('email', 'like', '%.staff@example.com')->count())->toBe(11)
        ->and(User::where('email', 'coe.staff@example.com')->sole()->office_id)
        ->toBe(Office::where('code', 'COE')->value('id'));
});

it('keeps each voucher\'s stage, dates, and routing history consistent with its status', function () {
    DisbursementVoucher::with(['currentStage', 'routingHistories'])->get()->each(function (DisbursementVoucher $voucher) {
        $lastAction = $voucher->routingHistories->sortBy('acted_at')->last()?->action;

        match ($voucher->status) {
            'draft' => expect($voucher->routingHistories)->toBeEmpty()
                ->and($voucher->current_stage_id)->toBeNull()
                ->and($voucher->submitted_at)->toBeNull(),
            'submitted' => expect($lastAction)->toBeIn(['submitted', 'resubmitted'])
                ->and($voucher->currentStage->name)->toBe('Finance Processor'),
            'returned' => expect($lastAction)->toBe('returned')
                ->and($voucher->current_stage_id)->toBeNull(),
            'for_payment' => expect($lastAction)->toBe('forwarded')
                ->and($voucher->currentStage->name)->toBe('Supervisor'),
            'completed' => expect($lastAction)->toBe('completed')
                ->and($voucher->completed_at)->not->toBeNull(),
        };
    });

    expect(RoutingHistory::where('action', 'returned')->whereNull('remarks')->exists())->toBeFalse();
});

it('does not duplicate vouchers when seeded again', function () {
    $count = DisbursementVoucher::count();

    $this->seed(DisbursementVoucherSeeder::class);

    expect(DisbursementVoucher::count())->toBe($count);
});
