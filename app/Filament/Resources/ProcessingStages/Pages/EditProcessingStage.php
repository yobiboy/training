<?php

namespace App\Filament\Resources\ProcessingStages\Pages;

use App\Filament\Resources\ProcessingStages\ProcessingStageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProcessingStage extends EditRecord
{
    protected static string $resource = ProcessingStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
