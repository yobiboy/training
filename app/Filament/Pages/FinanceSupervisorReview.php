<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DisbursementVouchers\Schemas\DisbursementVoucherDetails;
use App\Filament\Widgets\FinanceSupervisorDvOverview;
use App\Models\DisbursementVoucher;
use App\Models\RoutingHistory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceSupervisorReview extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.finance-supervisor-review';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'For Completion';

    protected static ?string $title = 'For Completion';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('finance_supervisor') ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Vouchers forwarded by the Finance Processor. Mark each one completed once it has been paid.';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = DisbursementVoucher::query()->awaitingSupervisor()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Vouchers ready for completion';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DisbursementVoucher::query()->awaitingSupervisor())
            ->emptyStateHeading('Nothing to complete')
            ->emptyStateDescription('Vouchers forwarded by the Finance Processor will appear here.')
            ->emptyStateIcon(Heroicon::OutlinedCheckBadge)
            ->columns([
                TextColumn::make('dv_no')
                    ->label('DV No.')
                    ->searchable(),
                TextColumn::make('payee_name')
                    ->searchable(),
                TextColumn::make('createdBy.office.name')
                    ->label('Office'),
                TextColumn::make('fund.name')
                    ->label('Fund'),
                TextColumn::make('amount')
                    ->money('PHP')
                    ->sortable(),
                TextColumn::make('particulars')
                    ->limit(50),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title())
                    ->color('warning'),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordAction('view')
            ->recordActions([
                ViewAction::make()
                    ->schema(DisbursementVoucherDetails::components())
                    ->slideOver(),
                Action::make('markCompleted')
                    ->label('Mark Completed')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This voucher will be marked as completed and its completion date set to now.')
                    ->action(fn (DisbursementVoucher $record) => $this->markCompleted($record)),
            ]);
    }

    protected function markCompleted(DisbursementVoucher $record): void
    {
        $record->refresh();

        if ($record->status !== 'for_payment') {
            Notification::make()
                ->danger()
                ->title('This voucher is no longer awaiting completion.')
                ->body('Someone else may have already acted on it. The list has been refreshed.')
                ->send();

            return;
        }

        DB::transaction(function () use ($record) {
            $record->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            RoutingHistory::create([
                'disbursement_voucher_id' => $record->id,
                'processing_stage_id' => $record->current_stage_id,
                'action' => 'completed',
                'acted_at' => now(),
            ]);
        });

        Notification::make()
            ->success()
            ->title('Voucher marked as completed')
            ->send();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceSupervisorDvOverview::class,
        ];
    }
}
