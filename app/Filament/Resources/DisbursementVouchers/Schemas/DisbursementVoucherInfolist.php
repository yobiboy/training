<?php

namespace App\Filament\Resources\DisbursementVouchers\Schemas;

use App\Models\DisbursementVoucher;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DisbursementVoucherInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('dv_no'),
                TextEntry::make('payee_name'),
                TextEntry::make('fund.name')
                    ->label('Fund'),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('particulars')
                    ->columnSpanFull(),
                TextEntry::make('currentStage.name')
                    ->label('Current stage')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('submitted_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('completed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('createdBy.name')
                    ->label('Created by'),
                TextEntry::make('updatedBy.name')
                    ->label('Updated by')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (DisbursementVoucher $record): bool => $record->trashed()),
            ]);
    }
}
