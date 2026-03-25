<?php

namespace App\Filament\User\Resources\WalletTransactions;

use App\Filament\User\Resources\WalletTransactions\Pages\ListWalletTransactions;
use App\Filament\User\Resources\WalletTransactions\Tables\WalletTransactionsTable;
use App\Models\WalletTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Transaction Log';

    protected static string|UnitEnum|null $navigationGroup = 'Funding';

    public static function canViewAny(): bool
    {
        return auth('web')->check();
    }

    public static function table(Table $table): Table
    {
        return WalletTransactionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWalletTransactions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $userId = auth('web')->id();

        return parent::getEloquentQuery()
            ->where('user_id', $userId ?? 0);
    }
}
