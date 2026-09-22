<?php

namespace App\Filament\Resources\RoutingHistories\Pages;

use App\Filament\Resources\RoutingHistories\RoutingHistoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRoutingHistory extends ViewRecord
{
    protected static string $resource = RoutingHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
