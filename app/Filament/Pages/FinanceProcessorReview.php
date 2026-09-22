<?php

namespace App\Filament\Pages;

use App\Models\DisbursementVoucher;
use App\Models\Office;
use App\Models\ProcessingStage;
use App\Models\RoutingHistory;
use BackedEnum;
use Filament\Actions\Action;
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

    protected static ?string $navigationLabel = 'Finance Processor';

    protected static ?string $title = 'Submitted Disbursement Vouchers';

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('Finance Processor') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DisbursementVoucher::query()->where('status', 'submitted'))
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
            ->recordActions([
                Action::make('endorse')
                    ->label('Endorse to Finance Supervisor')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This voucher will be forwarded to the Finance Supervisor for approval.')
                    ->action(fn (DisbursementVoucher $record) => $this->endorse($record)),
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

    protected function endorse(DisbursementVoucher $record): void
    {
        $nextStage = $this->nextStageAfter($record)->firstOrFail();

        DB::transaction(function () use ($record, $nextStage) {
            $record->update([
                'status' => 'in_process',
                'current_stage_id' => $nextStage->id,
            ]);

            RoutingHistory::create([
                'disbursement_voucher_id' => $record->id,
                'processing_stage_id' => $nextStage->id,
                'action' => 'forwarded',
                'acted_at' => now(),
            ]);
        });

        Notification::make()
            ->success()
            ->title('Voucher endorsed to Finance Supervisor')
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

    /**
     * The stage that follows the voucher's current one, ordered by sequence.
     * A freshly-submitted voucher has no stage yet, so this resolves to the
     * workflow's first stage instead. Deliberately not keyed by stage name —
     * stage names are admin-editable via the Processing Stages resource.
     */
    protected function nextStageAfter(DisbursementVoucher $record): Builder
    {
        $currentSequence = $record->current_stage_id
            ? ProcessingStage::find($record->current_stage_id)?->sequence
            : null;

        return ProcessingStage::query()
            ->when(
                $currentSequence !== null,
                fn ($query) => $query->where('sequence', '>', $currentSequence),
            )
            ->orderBy('sequence');
    }
}
