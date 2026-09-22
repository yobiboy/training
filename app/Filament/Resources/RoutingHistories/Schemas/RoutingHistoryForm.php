<?php

namespace App\Filament\Resources\RoutingHistories\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoutingHistoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('disbursement_voucher_id')
                    ->relationship('disbursementVoucher', 'dv_no')
                    ->required(),
                Select::make('processing_stage_id')
                    ->relationship('processingStage', 'name')
                    ->required(),
                TextInput::make('action')
                    ->required(),
                Textarea::make('remarks')
                    ->columnSpanFull(),
                DateTimePicker::make('acted_at')
                    ->required(),
            ]);
    }
}
