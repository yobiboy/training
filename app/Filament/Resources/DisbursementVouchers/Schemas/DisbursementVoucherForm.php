<?php

namespace App\Filament\Resources\DisbursementVouchers\Schemas;

use App\Models\DisbursementVoucher;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DisbursementVoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('dv_no')
                    ->default(fn (): string => DisbursementVoucher::generateNextDvNo())
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('payee_name')
                    ->required(),
                Select::make('fund_id')
                    ->relationship('fund', 'name')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Textarea::make('particulars')
                    ->required()
                    ->columnSpanFull(),
                Select::make('current_stage_id')
                    ->relationship('currentStage', 'name'),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'in_process' => 'In Process',
                        'returned' => 'Returned',
                        'for_payment' => 'For Payment',
                        'completed' => 'Completed',
                    ])
                    ->required(),
                DateTimePicker::make('submitted_at'),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
