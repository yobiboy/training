<?php

namespace App\Filament\Resources\DisbursementVouchers\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

/**
 * Read-only voucher details and routing history, shared by the role
 * dashboards' "View" actions.
 */
class DisbursementVoucherDetails
{
    /**
     * @return array<int, Section>
     */
    public static function components(): array
    {
        return [
            Section::make('Voucher')
                ->columns(2)
                ->schema([
                    TextEntry::make('dv_no')
                        ->label('DV No.'),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),
                    TextEntry::make('payee_name'),
                    TextEntry::make('fund.name')
                        ->label('Fund'),
                    TextEntry::make('amount')
                        ->money('PHP'),
                    TextEntry::make('currentStage.name')
                        ->label('Current stage')
                        ->placeholder('—'),
                    TextEntry::make('particulars')
                        ->columnSpanFull(),
                    TextEntry::make('createdBy.name')
                        ->label('Requested by'),
                    TextEntry::make('createdBy.office.name')
                        ->label('Office')
                        ->placeholder('—'),
                    TextEntry::make('submitted_at')
                        ->dateTime()
                        ->placeholder('—'),
                    TextEntry::make('completed_at')
                        ->dateTime()
                        ->placeholder('—'),
                ]),
            Section::make('Routing history')
                ->schema([
                    RepeatableEntry::make('routingHistories')
                        ->hiddenLabel()
                        ->placeholder('No routing activity yet.')
                        ->table([
                            TableColumn::make('Date'),
                            TableColumn::make('Stage'),
                            TableColumn::make('Action'),
                            TableColumn::make('By'),
                            TableColumn::make('Remarks'),
                        ])
                        ->schema([
                            TextEntry::make('acted_at')
                                ->dateTime(),
                            TextEntry::make('processingStage.name'),
                            TextEntry::make('action')
                                ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                            TextEntry::make('actedBy.name'),
                            TextEntry::make('remarks')
                                ->placeholder('—'),
                        ]),
                ]),
        ];
    }
}
