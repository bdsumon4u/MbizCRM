<?php

namespace App\Filament\Resources\Admin\UserRateBuckets;

use App\Filament\Resources\Admin\UserRateBuckets\Pages\CreateUserRateBucket;
use App\Filament\Resources\Admin\UserRateBuckets\Pages\EditUserRateBucket;
use App\Filament\Resources\Admin\UserRateBuckets\Pages\ListUserRateBuckets;
use App\Filament\Resources\Admin\UserRateBuckets\Schemas\UserRateBucketForm;
use App\Filament\Resources\Admin\UserRateBuckets\Tables\UserRateBucketsTable;
use App\Models\UserRateBucket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserRateBucketResource extends Resource
{
    protected static ?string $model = UserRateBucket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'User Rate Buckets';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UserRateBucketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserRateBucketsTable::configure($table);
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
            'index' => ListUserRateBuckets::route('/'),
            'create' => CreateUserRateBucket::route('/create'),
            'edit' => EditUserRateBucket::route('/{record}/edit'),
        ];
    }
}
