<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DisbursementVouchers\Schemas\DisbursementVoucherDetails;
use App\Filament\Widgets\FinanceProcessorDvOverview;
use App\Models\DisbursementVoucher;
use App\Models\Office;
use App\Models\ProcessingStage;
use App\Models\RoutingHistory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceProcessorReview extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.finance-processor-review';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Review Queue';

    protected static ?string $title = 'Review Queue';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('finance_processor') ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Check each submitted voucher, then forward it to the Supervisor or return it to the requesting unit with remarks.';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = DisbursementVoucher::query()->awaitingFinanceProcessor()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Vouchers waiting for your review';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DisbursementVoucher::query()->awaitingFinanceProcessor())
            ->emptyStateHeading('Your review queue is clear')
            ->emptyStateDescription('Newly submitted vouchers will appear here.')
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
                    ->color('info'),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('office')
                    ->options(fn () => Office::query()->where('is_active', true)->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $officeId): Builder => $query->whereHas(
                                'createdBy',
                                fn (Builder $query) => $query->where('office_id', $officeId),
                            ),
                        );
                    }),
            ])
            ->recordAction('view')
            ->recordActions([
                ViewAction::make()
                    ->schema(DisbursementVoucherDetails::components())
                    ->slideOver(),
                Action::make('forward')
                    ->label('Forward to Supervisor')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This voucher will be marked for payment and forwarded to the Supervisor.')
                    ->action(fn (DisbursementVoucher $record) => $this->forward($record)),
                Action::make('return')
                    ->label('Return with remarks')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('danger')
                    ->schema([
                        Textarea::make('remarks')
                            ->label('Remarks')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(fn (DisbursementVoucher $record, array $data) => $this->returnVoucher($record, $data['remarks'])),
            ]);
    }

    protected function forward(DisbursementVoucher $record): void
    {
        $supervisorStage = ProcessingStage::query()->where('name', ProcessingStage::SUPERVISOR)->firstOrFail();

        DB::transaction(function () use ($record, $supervisorStage) {
            $record->update([
                'status' => 'for_payment',
                'current_stage_id' => $supervisorStage->id,
            ]);

            RoutingHistory::create([
                'disbursement_voucher_id' => $record->id,
                'processing_stage_id' => $supervisorStage->id,
                'action' => 'forwarded',
                'acted_at' => now(),
            ]);
        });

        Notification::make()
            ->success()
            ->title('Voucher forwarded to the Supervisor')
            ->send();
    }

    protected function returnVoucher(DisbursementVoucher $record, string $remarks): void
    {
        // The voucher's own stage if it has one, otherwise the earliest stage
        // in the workflow — routing history always needs a stage to log against.
        $stageId = $record->current_stage_id ?? ProcessingStage::query()->orderBy('sequence')->value('id');

        DB::transaction(function () use ($record, $stageId, $remarks) {
            $record->update([
                'status' => 'returned',
                'current_stage_id' => null,
            ]);

            RoutingHistory::create([
                'disbursement_voucher_id' => $record->id,
                'processing_stage_id' => $stageId,
                'action' => 'returned',
                'remarks' => $remarks,
                'acted_at' => now(),
            ]);
        });

        Notification::make()
            ->warning()
            ->title('Voucher returned to the requesting unit')
            ->send();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceProcessorDvOverview::class,
        ];
    }
}
