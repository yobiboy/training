<?php

namespace App\Filament\Resources\ProcessingStages;

use App\Filament\Resources\ProcessingStages\Pages\CreateProcessingStage;
use App\Filament\Resources\ProcessingStages\Pages\EditProcessingStage;
use App\Filament\Resources\ProcessingStages\Pages\ListProcessingStages;
use App\Filament\Resources\ProcessingStages\Pages\ViewProcessingStage;
use App\Filament\Resources\ProcessingStages\Schemas\ProcessingStageForm;
use App\Filament\Resources\ProcessingStages\Schemas\ProcessingStageInfolist;
use App\Filament\Resources\ProcessingStages\Tables\ProcessingStagesTable;
use App\Models\ProcessingStage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProcessingStageResource extends Resource
{
    protected static ?string $model = ProcessingStage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProcessingStageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProcessingStageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProcessingStagesTable::configure($table);
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
            'index' => ListProcessingStages::route('/'),
            'create' => CreateProcessingStage::route('/create'),
            'view' => ViewProcessingStage::route('/{record}'),
            'edit' => EditProcessingStage::route('/{record}/edit'),
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
