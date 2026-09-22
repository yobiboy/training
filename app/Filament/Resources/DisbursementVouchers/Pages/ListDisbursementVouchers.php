<?php

namespace App\Filament\Resources\DisbursementVouchers\Pages;

use App\Filament\Resources\DisbursementVouchers\DisbursementVoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDisbursementVouchers extends ListRecords
{
    protected static string $resource = DisbursementVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
