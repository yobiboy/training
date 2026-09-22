<?php

namespace App\Filament\Resources\RoutingHistories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RoutingHistoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('disbursementVoucher.dv_no')
                    ->label('Disbursement voucher'),
                TextEntry::make('processingStage.name')
                    ->label('Processing stage'),
                TextEntry::make('action'),
                TextEntry::make('remarks')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('actedBy.name')
                    ->label('Acted by'),
                TextEntry::make('acted_at')
                    ->dateTime(),
            ]);
    }
}
