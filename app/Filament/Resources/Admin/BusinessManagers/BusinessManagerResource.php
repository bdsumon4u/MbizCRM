<?php

namespace App\Filament\Resources\Admin\BusinessManagers;

use App\Filament\Resources\Admin\BusinessManagers\Pages\CreateBusinessManager;
use App\Filament\Resources\Admin\BusinessManagers\Pages\EditBusinessManager;
use App\Filament\Resources\Admin\BusinessManagers\Pages\ListBusinessManagers;
use App\Filament\Resources\Admin\BusinessManagers\RelationManagers\AdAccountsRelationManager;
use App\Filament\Resources\Admin\BusinessManagers\Schemas\BusinessManagerForm;
use App\Filament\Resources\Admin\BusinessManagers\Tables\BusinessManagersTable;
use App\Models\BusinessManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BusinessManagerResource extends Resource
{
    protected static ?string $model = BusinessManager::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BusinessManagerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessManagersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AdAccountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessManagers::route('/'),
            // 'create' => CreateBusinessManager::route('/create'),
            'edit' => EditBusinessManager::route('/{record}/edit'),
        ];
    }
}
