<?php

namespace App\Filament\Resources\RoutingHistories\Pages;

use App\Filament\Resources\RoutingHistories\RoutingHistoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRoutingHistory extends EditRecord
{
    protected static string $resource = RoutingHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
