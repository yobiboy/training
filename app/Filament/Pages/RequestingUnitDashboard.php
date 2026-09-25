<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DisbursementVouchers\Schemas\DisbursementVoucherDetails;
use App\Filament\Widgets\RequestingUnitDvOverview;
use App\Models\DisbursementVoucher;
use App\Models\Fund;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestingUnitDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.requesting-unit-dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $navigationLabel = 'My Vouchers';

    protected static ?string $title = 'My Vouchers';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('requesting_unit') ?? false;
    }

    public function getSubheading(): ?string
    {
        $officeName = Auth::user()?->office?->name;

        return ($officeName ? "Vouchers from {$officeName}. " : '').'Create a voucher, save it as a draft, and follow it as it moves through Finance.';
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $returnedCount = DisbursementVoucher::query()->visibleToOfficeOf($user)->where('status', 'returned')->count();

        return $returnedCount > 0 ? (string) $returnedCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Returned vouchers that need your edits';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DisbursementVoucher::query()->visibleToOfficeOf(Auth::user()))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No vouchers yet')
            ->emptyStateDescription('Create your first disbursement voucher using the button above.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentPlus)
            ->columns([
                TextColumn::make('dv_no')
                    ->label('DV No.')
                    ->searchable(),
                TextColumn::make('payee_name')
                    ->searchable(),
                TextColumn::make('fund.name')
                    ->label('Fund'),
                TextColumn::make('amount')
                    ->money('PHP')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => DisbursementVoucher::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'in_process', 'for_payment' => 'warning',
                        'returned' => 'danger',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('currentStage.name')
                    ->label('Current stage')
                    ->placeholder('—'),
                TextColumn::make('createdBy.name')
                    ->label('Created by'),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(DisbursementVoucher::STATUS_LABELS),
            ])
            ->headerActions([
                $this->voucherFormAction('create')
                    ->label('New disbursement voucher')
                    ->icon(Heroicon::OutlinedPlus)
                    ->modalHeading('New disbursement voucher')
                    ->action(fn (array $data, array $arguments) => $this->createVoucher($data, $arguments['draft'] ?? false)),
            ])
            ->recordAction('view')
            ->recordActions([
                ViewAction::make()
                    ->schema(DisbursementVoucherDetails::components())
                    ->slideOver(),
                $this->voucherFormAction('edit')
                    ->label('Edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalHeading(fn (DisbursementVoucher $record): string => "Edit {$record->dv_no}")
                    ->fillForm(fn (DisbursementVoucher $record): array => $record->only(['payee_name', 'fund_id', 'amount', 'particulars']))
                    ->visible(fn (DisbursementVoucher $record): bool => in_array($record->status, ['draft', 'returned'], true))
                    ->action(fn (DisbursementVoucher $record, array $data, array $arguments) => $this->updateVoucher($record, $data, $arguments['draft'] ?? false)),
                Action::make('submit')
                    ->label('Submit')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Once submitted, this voucher is forwarded to the Finance Processor and can no longer be edited.')
                    ->visible(fn (DisbursementVoucher $record): bool => $record->status === 'draft')
                    ->action(function (DisbursementVoucher $record): void {
                        $record->submit();

                        $this->notifySaved(submitted: true);
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (DisbursementVoucher $record): string => "Delete {$record->dv_no}?")
                    ->modalDescription('This voucher will be removed from your list. Contact the administrator if you need it back.')
                    ->modalSubmitActionLabel('Yes, delete it')
                    ->visible(fn (DisbursementVoucher $record): bool => $record->isDeletableByRequester())
                    ->action(function (DisbursementVoucher $record): void {
                        $record->deleteByRequester();

                        Notification::make()
                            ->success()
                            ->title("{$record->dv_no} deleted")
                            ->send();
                    }),
            ]);
    }

    /**
     * A modal action holding the voucher form, whose primary button submits
     * the voucher and whose extra button saves without submitting. A returned
     * voucher can only be resubmitted through this form, so it must be edited first.
     */
    protected function voucherFormAction(string $name): Action
    {
        return Action::make($name)
            ->schema([
                TextEntry::make('return_remarks')
                    ->label('Returned with remarks')
                    ->state(fn (?DisbursementVoucher $record): ?string => $record?->latestReturnRemarks())
                    ->color('danger')
                    ->visible(fn (?DisbursementVoucher $record): bool => $record?->status === 'returned')
                    ->columnSpanFull(),
                TextInput::make('payee_name')
                    ->required()
                    ->maxLength(200),
                Select::make('fund_id')
                    ->label('Fund')
                    ->options(fn () => Fund::query()->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('₱')
                    ->required(),
                Textarea::make('particulars')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->modalSubmitActionLabel('Submit')
            ->extraModalFooterActions(fn (Action $action, ?DisbursementVoucher $record): array => [
                $action->makeModalSubmitAction('saveDraft', arguments: ['draft' => true])
                    ->label($record?->status === 'returned' ? 'Save changes' : 'Save as draft')
                    ->color('gray'),
            ]);
    }

    /**
     * @param  array{payee_name: string, fund_id: int, amount: numeric-string|float, particulars: string}  $data
     */
    protected function createVoucher(array $data, bool $isDraft): void
    {
        DB::transaction(function () use ($data, $isDraft) {
            $voucher = DisbursementVoucher::create([
                ...$data,
                'dv_no' => DisbursementVoucher::generateNextDvNo(),
                'status' => 'draft',
            ]);

            if (! $isDraft) {
                $voucher->submit();
            }
        });

        $this->notifySaved(submitted: ! $isDraft);
    }

    /**
     * @param  array{payee_name: string, fund_id: int, amount: numeric-string|float, particulars: string}  $data
     */
    protected function updateVoucher(DisbursementVoucher $record, array $data, bool $isDraft): void
    {
        DB::transaction(function () use ($record, $data, $isDraft) {
            $record->update($data);

            if (! $isDraft) {
                $record->submit();
            }
        });

        $this->notifySaved(submitted: ! $isDraft);
    }

    protected function notifySaved(bool $submitted): void
    {
        Notification::make()
            ->success()
            ->title($submitted ? 'Disbursement voucher submitted' : 'Disbursement voucher saved')
            ->send();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RequestingUnitDvOverview::class,
        ];
    }
}
