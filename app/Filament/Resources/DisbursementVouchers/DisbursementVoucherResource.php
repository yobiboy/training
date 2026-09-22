<?php

namespace App\Filament\Resources\DisbursementVouchers;

use App\Filament\Resources\DisbursementVouchers\Pages\CreateDisbursementVoucher;
use App\Filament\Resources\DisbursementVouchers\Pages\EditDisbursementVoucher;
use App\Filament\Resources\DisbursementVouchers\Pages\ListDisbursementVouchers;
use App\Filament\Resources\DisbursementVouchers\Pages\ViewDisbursementVoucher;
use App\Filament\Resources\DisbursementVouchers\Schemas\DisbursementVoucherForm;
use App\Filament\Resources\DisbursementVouchers\Schemas\DisbursementVoucherInfolist;
use App\Filament\Resources\DisbursementVouchers\Tables\DisbursementVouchersTable;
use App\Models\DisbursementVoucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DisbursementVoucherResource extends Resource
{
    protected static ?string $model = DisbursementVoucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'dv_no';

    public static function form(Schema $schema): Schema
    {
        return DisbursementVoucherForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DisbursementVoucherInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DisbursementVouchersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisbursementVouchers::route('/'),
            'create' => CreateDisbursementVoucher::route('/create'),
            'view' => ViewDisbursementVoucher::route('/{record}'),
            'edit' => EditDisbursementVoucher::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
