<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\FinanceProcessorReview;
use App\Filament\Pages\FinanceSupervisorReview;
use App\Filament\Pages\RequestingUnitDashboard;
use App\Filament\Resources\DisbursementVouchers\DisbursementVoucherResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\DisbursementVoucher;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Dashboard greeting banner: who is signed in, what their role is for, the
 * next thing they should do, and where their work sits in the voucher flow.
 */
class WelcomeWidget extends Widget
{
    protected string $view = 'filament.widgets.welcome-widget';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    /**
     * The workflow steps shown in the banner, in order.
     *
     * @var array<string, string>
     */
    public const WORKFLOW_STEPS = [
        'requesting_unit' => 'Prepare & submit',
        'finance_processor' => 'Finance review',
        'finance_supervisor' => 'Supervisor approval',
        'completed' => 'Completed',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $role = $user->primaryRole();

        return [
            'greeting' => $this->greeting(),
            'firstName' => str($user->name)->before(' ')->toString(),
            'roleLabel' => match ($role) {
                'requesting_unit' => 'Requesting Unit',
                'finance_processor' => 'Finance Processor',
                'finance_supervisor' => 'Finance Supervisor',
                'super_admin' => 'Administrator',
                default => null,
            },
            'officeName' => $user->office?->name,
            'today' => now()->format('l, F j, Y'),
            'workflowSteps' => self::WORKFLOW_STEPS,
            'currentStep' => array_key_exists($role, self::WORKFLOW_STEPS) ? $role : null,
            ...$this->roleContent($role, $user),
        ];
    }

    /**
     * @return array{summary: string, actions: list<array{label: string, url: string, icon: Heroicon, primary: bool}>, attention: list<array{label: string, detail: ?string, url: string}>}
     */
    protected function roleContent(?string $role, User $user): array
    {
        return match ($role) {
            'requesting_unit' => $this->requestingUnitContent($user),
            'finance_processor' => $this->queueContent(
                count: DisbursementVoucher::query()->awaitingFinanceProcessor()->count(),
                noun: 'waiting for your review',
                emptySummary: 'Your review queue is clear. Nice work!',
                label: 'Open review queue',
                url: FinanceProcessorReview::getUrl(),
            ),
            'finance_supervisor' => $this->queueContent(
                count: DisbursementVoucher::query()->awaitingSupervisor()->count(),
                noun: 'ready for completion',
                emptySummary: 'Nothing is waiting for completion right now.',
                label: 'Open completion queue',
                url: FinanceSupervisorReview::getUrl(),
            ),
            'super_admin' => [
                'summary' => 'Manage vouchers, users, offices, funds, and roles from the menu.',
                'actions' => [
                    ['label' => 'All vouchers', 'url' => DisbursementVoucherResource::getUrl(), 'icon' => Heroicon::OutlinedDocumentText, 'primary' => true],
                    ['label' => 'Users', 'url' => UserResource::getUrl(), 'icon' => Heroicon::OutlinedUsers, 'primary' => false],
                ],
                'attention' => [],
            ],
            default => ['summary' => 'Your account has no voucher role yet. Please contact the administrator.', 'actions' => [], 'attention' => []],
        };
    }

    /**
     * @return array{summary: string, actions: list<array{label: string, url: string, icon: Heroicon, primary: bool}>, attention: list<array{label: string, detail: ?string, url: string}>}
     */
    protected function requestingUnitContent(User $user): array
    {
        $officeVouchers = DisbursementVoucher::query()->visibleToOfficeOf($user);
        $returned = $officeVouchers->clone()->where('status', 'returned')->latest('updated_at')->limit(3)->get();
        $returnedCount = $officeVouchers->clone()->where('status', 'returned')->count();
        $inProgressCount = $officeVouchers->clone()->whereIn('status', ['submitted', 'for_payment'])->count();

        return [
            'summary' => match (true) {
                $returnedCount > 0 => trans_choice('{1} 1 voucher was returned and needs your edits.|[2,*] :count vouchers were returned and need your edits.', $returnedCount),
                $inProgressCount > 0 => trans_choice('{1} 1 voucher is moving through Finance.|[2,*] :count vouchers are moving through Finance.', $inProgressCount),
                default => 'Start a new disbursement voucher whenever you are ready.',
            },
            'actions' => [
                ['label' => 'Create a voucher', 'url' => RequestingUnitDashboard::getUrl(['tableAction' => 'create']), 'icon' => Heroicon::OutlinedPlus, 'primary' => true],
                ['label' => 'View my vouchers', 'url' => RequestingUnitDashboard::getUrl(), 'icon' => Heroicon::OutlinedQueueList, 'primary' => false],
            ],
            'attention' => $returned->map(fn (DisbursementVoucher $voucher): array => [
                'label' => "{$voucher->dv_no} · {$voucher->payee_name}",
                'detail' => $voucher->latestReturnRemarks(),
                'url' => RequestingUnitDashboard::getUrl(['tableAction' => 'edit', 'tableActionRecord' => $voucher->getKey()]),
            ])->all(),
        ];
    }

    /**
     * @return array{summary: string, actions: list<array{label: string, url: string, icon: Heroicon, primary: bool}>, attention: list<array{label: string, detail: ?string, url: string}>}
     */
    protected function queueContent(int $count, string $noun, string $emptySummary, string $label, string $url): array
    {
        return [
            'summary' => $count > 0
                ? trans_choice("{1} 1 voucher is {$noun}.|[2,*] :count vouchers are {$noun}.", $count)
                : $emptySummary,
            'actions' => [
                ['label' => $label, 'url' => $url, 'icon' => Heroicon::OutlinedInboxArrowDown, 'primary' => true],
            ],
            'attention' => [],
        ];
    }

    protected function greeting(): string
    {
        return match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };
    }
}
