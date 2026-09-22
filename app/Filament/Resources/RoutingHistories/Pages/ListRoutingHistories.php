<?php

namespace App\Filament\Resources\RoutingHistories\Pages;

use App\Filament\Resources\RoutingHistories\RoutingHistoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoutingHistories extends ListRecords
{
    protected static string $resource = RoutingHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
