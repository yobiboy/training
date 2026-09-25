<?php

namespace App\Filament\Widgets;

use App\Models\DisbursementVoucher;
use App\Models\RoutingHistory;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class FinanceProcessorDvOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return Auth::user()?->hasRole('finance_processor') ?? false;
    }

    protected function getStats(): array
    {
        $awaitingReview = DisbursementVoucher::query()->awaitingFinanceProcessor();

        $countThisMonth = fn (string $action): int => RoutingHistory::query()
            ->where('action', $action)
            ->where('acted_at', '>=', now()->startOfMonth())
            ->count();

        return [
            Stat::make('Awaiting review', $awaitingReview->clone()->count())
                ->description(Number::currency((float) $awaitingReview->clone()->sum('amount'), 'PHP').' total')
                ->descriptionIcon(Heroicon::OutlinedInboxStack)
                ->color('info'),
            Stat::make('Resubmitted after return', $awaitingReview->clone()->whereRelation('routingHistories', 'action', 'resubmitted')->count())
                ->description('In the queue now')
                ->descriptionIcon(Heroicon::OutlinedArrowPath)
                ->color('warning'),
            Stat::make('Forwarded this month', $countThisMonth('forwarded'))
                ->description('Sent to the Supervisor')
                ->descriptionIcon(Heroicon::OutlinedArrowRightCircle)
                ->color('success'),
            Stat::make('Returned this month', $countThisMonth('returned'))
                ->description('Sent back to requesting units')
                ->descriptionIcon(Heroicon::OutlinedArrowUturnLeft)
                ->color('danger'),
        ];
    }
}
