<?php

namespace App\Filament\Pages;

use App\Models\DisbursementVoucher;
use App\Models\RoutingHistory;
use BackedEnum;
use Filament\Actions\Action;
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

    protected static ?string $navigationLabel = 'Finance Supervisor';

    protected static ?string $title = 'Endorsed Disbursement Vouchers';

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('Finance Supervisor') ?? false;
    }

    /**
     * status => the status a voucher must currently have for this
     * transition to be allowed. Enforces the two-step flow: a voucher must
     * be marked for payment before it can be marked completed.
     */
    private const TRANSITIONS = [
        'for_payment' => 'in_process',
        'completed' => 'for_payment',
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(DisbursementVoucher::query()->whereIn('status', ['in_process', 'for_payment']))
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
                    ->numeric()
                    ->sortable(),
                TextColumn::make('particulars')
                    ->limit(50),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title())
                    ->color(fn (string $state): string => $state === 'for_payment' ? 'warning' : 'gray'),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('markForPayment')
                    ->label('Mark for Payment')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color('warning')
                    ->visible(fn (DisbursementVoucher $record): bool => $record->status === 'in_process')
                    ->requiresConfirmation()
                    ->modalDescription('This voucher will be marked as ready for payment.')
                    ->action(fn (DisbursementVoucher $record) => $this->updateStatus($record, 'for_payment')),
                Action::make('markCompleted')
                    ->label('Mark Completed')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (DisbursementVoucher $record): bool => $record->status === 'for_payment')
                    ->requiresConfirmation()
                    ->modalDescription('This voucher will be marked as completed.')
                    ->action(fn (DisbursementVoucher $record) => $this->updateStatus($record, 'completed')),
            ]);
    }

    protected function updateStatus(DisbursementVoucher $record, string $status): void
    {
        $record->refresh();

        if ($record->status !== self::TRANSITIONS[$status]) {
            Notification::make()
                ->danger()
                ->title('This voucher is no longer in the expected status.')
                ->body('Someone else may have already acted on it. The list has been refreshed.')
                ->send();

            return;
        }

        // A voucher only reaches this page via the Finance Processor's
        // "Endorse" action, which always sets current_stage_id — so it's
        // never null here. Reusing it avoids depending on a specific
        // stage's name, which is admin-editable via the Processing Stages
        // resource.
        DB::transaction(function () use ($record, $status) {
            $record->update([
                'status' => $status,
                'completed_at' => $status === 'completed' ? now() : $record->completed_at,
            ]);

            RoutingHistory::create([
                'disbursement_voucher_id' => $record->id,
                'processing_stage_id' => $record->current_stage_id,
                'action' => 'processed',
                'acted_at' => now(),
            ]);
        });

        Notification::make()
            ->success()
            ->title($status === 'completed' ? 'Voucher marked as completed' : 'Voucher marked for payment')
            ->send();
    }
}
