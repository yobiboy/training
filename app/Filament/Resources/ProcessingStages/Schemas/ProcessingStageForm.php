<?php

namespace App\Filament\Resources\ProcessingStages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProcessingStageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('sequence')
                    ->required()
                    ->numeric(),
                Toggle::make('is_required')
                    ->required(),
            ]);
    }
}
