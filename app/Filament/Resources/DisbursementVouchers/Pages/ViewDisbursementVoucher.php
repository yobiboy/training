<?php

namespace App\Filament\Resources\DisbursementVouchers\Pages;

use App\Filament\Resources\DisbursementVouchers\DisbursementVoucherResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDisbursementVoucher extends ViewRecord
{
    protected static string $resource = DisbursementVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
