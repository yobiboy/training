<?php

namespace App\Filament\Resources\ProcessingStages\Pages;

use App\Filament\Resources\ProcessingStages\ProcessingStageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProcessingStages extends ListRecords
{
    protected static string $resource = ProcessingStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
