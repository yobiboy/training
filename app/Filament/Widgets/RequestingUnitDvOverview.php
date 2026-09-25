<?php

namespace App\Filament\Widgets;

use App\Models\DisbursementVoucher;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RequestingUnitDvOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return Auth::user()?->hasRole('requesting_unit') ?? false;
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        $countByStatus = fn (string $status): int => DisbursementVoucher::query()
            ->visibleToOfficeOf($user)
            ->where('status', $status)
            ->count();

        return [
            Stat::make('Draft DVs', $countByStatus('draft'))
                ->color('gray'),
            Stat::make('Submitted DVs', $countByStatus('submitted'))
                ->color('gray'),
            Stat::make('In-process DVs', $countByStatus('in_process'))
                ->color('warning'),
            Stat::make('Returned DVs', $countByStatus('returned'))
                ->color('danger'),
            Stat::make('Completed DVs', $countByStatus('completed'))
                ->color('success'),
        ];
    }
}
