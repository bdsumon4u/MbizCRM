<?php

namespace App\Filament\Resources\Admin\GlobalRateBuckets\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GlobalRateBucketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->maxLength(255),
                TextInput::make('min_usd_micros')
                    ->label('Min USD')
                    ->required()
                    ->numeric()
                    ->step('0.000001')
                    ->minValue(0)
                    ->helperText('Range semantics are [min, max). Example: 20.00 belongs to 20.00-50.00, not 0.00-20.00.')
                    ->formatStateUsing(fn ($state): ?string => self::formatDecimalState($state, 1_000_000))
                    ->dehydrateStateUsing(fn ($state): int => self::toScaledInteger($state, 1_000_000)),
                TextInput::make('max_usd_micros')
                    ->label('Max USD')
                    ->required()
                    ->numeric()
                    ->step('0.000001')
                    ->minValue(0)
                    ->formatStateUsing(fn ($state): ?string => self::formatDecimalState($state, 1_000_000))
                    ->dehydrateStateUsing(fn ($state): int => self::toScaledInteger($state, 1_000_000)),
                TextInput::make('bdt_per_usd_poisha')
                    ->label('Rate (BDT per 1 USD)')
                    ->required()
                    ->numeric()
                    ->step('0.01')
                    ->minValue(0.01)
                    ->formatStateUsing(fn ($state): ?string => self::formatDecimalState($state, 100))
                    ->dehydrateStateUsing(fn ($state): int => self::toScaledInteger($state, 100)),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    private static function formatDecimalState(mixed $state, int $scale): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        $value = ((int) $state) / $scale;

        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }

    private static function toScaledInteger(mixed $state, int $scale): int
    {
        return (int) round(((float) $state) * $scale);
    }
}
