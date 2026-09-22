<?php

namespace App\Filament\Resources\ProcessingStages\Schemas;

use App\Models\ProcessingStage;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProcessingStageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('sequence')
                    ->numeric(),
                IconEntry::make('is_required')
                    ->boolean(),
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
                    ->visible(fn (ProcessingStage $record): bool => $record->trashed()),
            ]);
    }
}
