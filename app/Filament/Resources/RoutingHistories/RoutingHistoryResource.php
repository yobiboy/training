<?php

namespace App\Filament\Resources\RoutingHistories;

use App\Filament\Resources\RoutingHistories\Pages\CreateRoutingHistory;
use App\Filament\Resources\RoutingHistories\Pages\EditRoutingHistory;
use App\Filament\Resources\RoutingHistories\Pages\ListRoutingHistories;
use App\Filament\Resources\RoutingHistories\Pages\ViewRoutingHistory;
use App\Filament\Resources\RoutingHistories\Schemas\RoutingHistoryForm;
use App\Filament\Resources\RoutingHistories\Schemas\RoutingHistoryInfolist;
use App\Filament\Resources\RoutingHistories\Tables\RoutingHistoriesTable;
use App\Models\RoutingHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RoutingHistoryResource extends Resource
{
    protected static ?string $model = RoutingHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'action';

    public static function form(Schema $schema): Schema
    {
        return RoutingHistoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RoutingHistoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoutingHistoriesTable::configure($table);
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
            'index' => ListRoutingHistories::route('/'),
            'create' => CreateRoutingHistory::route('/create'),
            'view' => ViewRoutingHistory::route('/{record}'),
            'edit' => EditRoutingHistory::route('/{record}/edit'),
        ];
    }
}
