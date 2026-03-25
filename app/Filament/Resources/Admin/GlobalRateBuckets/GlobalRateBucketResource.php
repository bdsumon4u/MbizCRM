<?php

namespace App\Filament\Resources\Admin\GlobalRateBuckets;

use App\Filament\Resources\Admin\GlobalRateBuckets\Pages\CreateGlobalRateBucket;
use App\Filament\Resources\Admin\GlobalRateBuckets\Pages\EditGlobalRateBucket;
use App\Filament\Resources\Admin\GlobalRateBuckets\Pages\ListGlobalRateBuckets;
use App\Filament\Resources\Admin\GlobalRateBuckets\Schemas\GlobalRateBucketForm;
use App\Filament\Resources\Admin\GlobalRateBuckets\Tables\GlobalRateBucketsTable;
use App\Models\GlobalRateBucket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GlobalRateBucketResource extends Resource
{
    protected static ?string $model = GlobalRateBucket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Global Rate Buckets';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return GlobalRateBucketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GlobalRateBucketsTable::configure($table);
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
            'index' => ListGlobalRateBuckets::route('/'),
            'create' => CreateGlobalRateBucket::route('/create'),
            'edit' => EditGlobalRateBucket::route('/{record}/edit'),
        ];
    }
}
