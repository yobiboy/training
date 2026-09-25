<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DisbursementVouchers\Schemas\DisbursementVoucherDetails;
use App\Models\DisbursementVoucher;
use App\Models\Fund;
use App\Models\Office;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A filterable list of every voucher that concerns the signed-in user's role,
 * downloadable as a spreadsheet (CSV) of exactly what the filters show.
 */
class Reports extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $title = 'Reports';

    protected static ?int $navigationSort = 2;

    /**
     * Spreadsheet columns: heading => value resolver.
     *
     * @return array<string, callable(DisbursementVoucher): mixed>
     */
    public static function spreadsheetColumns(): array
    {
        return [
            'DV No.' => fn (DisbursementVoucher $voucher): string => $voucher->dv_no,
            'Payee' => fn (DisbursementVoucher $voucher): string => $voucher->payee_name,
            'Office' => fn (DisbursementVoucher $voucher): ?string => $voucher->createdBy?->office?->name,
            'Fund Code' => fn (DisbursementVoucher $voucher): ?string => $voucher->fund?->code,
            'Fund' => fn (DisbursementVoucher $voucher): ?string => $voucher->fund?->name,
            'Amount (PHP)' => fn (DisbursementVoucher $voucher): string => number_format((float) $voucher->amount, 2, '.', ''),
            'Particulars' => fn (DisbursementVoucher $voucher): string => $voucher->particulars,
            'Status' => fn (DisbursementVoucher $voucher): string => DisbursementVoucher::STATUS_LABELS[$voucher->status] ?? $voucher->status,
            'Current Stage' => fn (DisbursementVoucher $voucher): ?string => $voucher->currentStage?->name,
            'Requested By' => fn (DisbursementVoucher $voucher): ?string => $voucher->createdBy?->name,
            'Date Created' => fn (DisbursementVoucher $voucher): ?string => $voucher->created_at?->format('Y-m-d H:i'),
            'Date Submitted' => fn (DisbursementVoucher $voucher): ?string => $voucher->submitted_at?->format('Y-m-d H:i'),
            'Date Completed' => fn (DisbursementVoucher $voucher): ?string => $voucher->completed_at?->format('Y-m-d H:i'),
            'Age (Days)' => fn (DisbursementVoucher $voucher): ?int => $voucher->ageInDays(),
            'Days at Current Stage' => fn (DisbursementVoucher $voucher): ?int => $voucher->daysAtCurrentStage(),
            'Aging Bucket' => fn (DisbursementVoucher $voucher): ?string => DisbursementVoucher::AGING_BUCKETS[$voucher->agingBucket()][0] ?? null,
        ];
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->primaryRole() !== null;
    }

    public function getSubheading(): ?string
    {
        return match (Auth::user()?->primaryRole()) {
            'requesting_unit' => 'All vouchers from your office.',
            'finance_processor' => 'All vouchers submitted to Finance.',
            'finance_supervisor' => 'All vouchers forwarded to the Supervisor.',
            default => 'All disbursement vouchers.',
        }.' Narrow them down with the filters, then download the list as a spreadsheet.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DisbursementVoucher::query()->reportableBy($this->user())->with(['fund', 'currentStage', 'createdBy.office'])->withMax('routingHistories', 'acted_at'))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No vouchers match these filters')
            ->emptyStateDescription('Try widening the date range or clearing a filter.')
            ->emptyStateIcon(Heroicon::OutlinedFunnel)
            ->columns([
                TextColumn::make('dv_no')
                    ->label('DV No.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payee_name')
                    ->searchable(),
                TextColumn::make('createdBy.office.name')
                    ->label('Office')
                    ->hidden(fn (): bool => $this->user()->primaryRole() === 'requesting_unit'),
                TextColumn::make('fund.name')
                    ->label('Fund')
                    ->toggleable(),
                TextColumn::make('amount')
                    ->money('PHP')
                    ->sortable()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => DisbursementVoucher::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'submitted' => 'info',
                        'for_payment' => 'warning',
                        'returned' => 'danger',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('age')
                    ->label('Age')
                    ->state(fn (DisbursementVoucher $record): ?int => $record->ageInDays())
                    ->formatStateUsing(fn (int $state): string => trans_choice('{1} 1 day|[0,*] :count days', $state))
                    ->badge()
                    ->color(fn (DisbursementVoucher $record): string => match ($record->status === 'completed' ? null : $record->agingBucket()) {
                        '8-15' => 'info',
                        '16-30' => 'warning',
                        '31+' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('—')
                    ->tooltip('Days since submission (until completion for completed vouchers)'),
                TextColumn::make('days_at_stage')
                    ->label('At stage')
                    ->state(fn (DisbursementVoucher $record): ?int => $record->daysAtCurrentStage())
                    ->formatStateUsing(fn (int $state): string => trans_choice('{1} 1 day|[0,*] :count days', $state))
                    ->placeholder('—')
                    ->tooltip('Days since the last routing step')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->date()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('created_between')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Created from'),
                        DatePicker::make('until')
                            ->label('Created until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(fn (array $data): array => array_filter([
                        filled($data['from'] ?? null) ? 'From '.$data['from'] : null,
                        filled($data['until'] ?? null) ? 'Until '.$data['until'] : null,
                    ])),
                SelectFilter::make('status')
                    ->multiple()
                    ->options(DisbursementVoucher::STATUS_LABELS),
                SelectFilter::make('aging')
                    ->label('Aging (open vouchers)')
                    ->options(collect(DisbursementVoucher::AGING_BUCKETS)->map(fn (array $bucket): string => $bucket[0]))
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $bucket): Builder => $query->openAndAged($bucket),
                    )),
                SelectFilter::make('fund_id')
                    ->label('Fund')
                    ->options(fn () => Fund::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
                SelectFilter::make('office')
                    ->options(fn () => Office::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->hidden(fn (): bool => $this->user()->primaryRole() === 'requesting_unit')
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $officeId): Builder => $query->whereRelation('createdBy', 'office_id', $officeId),
                    )),
            ])
            ->headerActions([
                Action::make('download')
                    ->label('Download spreadsheet')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (): StreamedResponse => $this->downloadSpreadsheet()),
            ])
            ->recordAction('view')
            ->recordActions([
                ViewAction::make()
                    ->schema(DisbursementVoucherDetails::components())
                    ->slideOver(),
            ]);
    }

    /**
     * Stream the currently filtered and sorted vouchers as a CSV file. A UTF-8
     * byte-order mark is written first so Excel shows ₱ and ñ correctly.
     */
    protected function downloadSpreadsheet(): StreamedResponse
    {
        $query = $this->getFilteredSortedTableQuery();
        $columns = self::spreadsheetColumns();
        $fileName = 'dv-report-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query, $columns): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_keys($columns), escape: '');

            foreach ($query->lazy() as $voucher) {
                fputcsv($handle, array_map(fn (callable $column): mixed => $column($voucher), $columns), escape: '');
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
