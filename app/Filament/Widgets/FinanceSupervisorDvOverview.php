<?php

namespace App\Filament\Widgets;

use App\Models\DisbursementVoucher;
use Carbon\CarbonInterface;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class FinanceSupervisorDvOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return Auth::user()?->hasRole('finance_supervisor') ?? false;
    }

    protected function getStats(): array
    {
        $awaitingCompletion = DisbursementVoucher::query()->awaitingSupervisor();

        $completedSince = fn (CarbonInterface $since): Builder => DisbursementVoucher::query()
            ->where('status', 'completed')
            ->where('completed_at', '>=', $since);

        $completedThisMonth = $completedSince(now()->startOfMonth());

        return [
            Stat::make('Awaiting completion', $awaitingCompletion->clone()->count())
                ->description('Forwarded and ready for payment')
                ->descriptionIcon(Heroicon::OutlinedInboxStack)
                ->color('warning'),
            Stat::make('Pending payment amount', Number::currency((float) $awaitingCompletion->clone()->sum('amount'), 'PHP'))
                ->description('Across vouchers awaiting completion')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('warning'),
            Stat::make('Completed today', $completedSince(now()->startOfDay())->count())
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('Completed this month', $completedThisMonth->clone()->count())
                ->description(Number::currency((float) $completedThisMonth->clone()->sum('amount'), 'PHP').' disbursed')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('success'),
        ];
    }
}
