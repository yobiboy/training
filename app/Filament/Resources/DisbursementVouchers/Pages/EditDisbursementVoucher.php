<?php

namespace App\Filament\Resources\DisbursementVouchers\Pages;

use App\Filament\Resources\DisbursementVouchers\DisbursementVoucherResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDisbursementVoucher extends EditRecord
{
    protected static string $resource = DisbursementVoucherResource::class;

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
