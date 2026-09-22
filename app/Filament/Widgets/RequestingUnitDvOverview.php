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
        return Auth::user()?->hasRole('Requesting Unit') ?? false;
    }

    protected function getStats(): array
    {
        $userId = Auth::id();

        $countByStatus = fn (string $status): int => DisbursementVoucher::where('created_by', $userId)
            ->where('status', $status)
            ->count();

        return [
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
