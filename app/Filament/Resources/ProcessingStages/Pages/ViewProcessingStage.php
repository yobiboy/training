<?php

namespace App\Filament\Resources\ProcessingStages\Pages;

use App\Filament\Resources\ProcessingStages\ProcessingStageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProcessingStage extends ViewRecord
{
    protected static string $resource = ProcessingStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
