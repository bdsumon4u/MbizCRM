<?php

namespace App\Filament\User\Resources\AdManagers;

use App\Filament\User\Resources\AdManagers\Pages\ListAdManagers;
use App\Filament\User\Resources\AdManagers\Tables\AdManagersTable;
use App\Models\BusinessManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AdManagerResource extends Resource
{
    protected static ?string $model = BusinessManager::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Ad Managers';

    protected static string|UnitEnum|null $navigationGroup = 'Funding';

    public static function canViewAny(): bool
    {
        return auth('web')->check();
    }

    public static function table(Table $table): Table
    {
        return AdManagersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdManagers::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $userId = auth('web')->id();

        return parent::getEloquentQuery()
            ->whereHas('adAccounts', fn (Builder $query): Builder => $query->where('user_id', $userId ?? 0))
            ->withCount([
                'adAccounts as assigned_ad_accounts_count' => fn (Builder $query): Builder => $query->where('user_id', $userId ?? 0),
            ]);
    }
}
