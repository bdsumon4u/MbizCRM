<?php

namespace App\Filament\Resources\Admin\UserRateBuckets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserRateBucketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('min_usd_micros')
                    ->label('Min USD')
                    ->formatStateUsing(fn ($state): string => number_format(((int) $state) / 1_000_000, 6, '.', ''))
                    ->sortable(),
                TextColumn::make('max_usd_micros')
                    ->label('Max USD')
                    ->formatStateUsing(fn ($state): string => number_format(((int) $state) / 1_000_000, 6, '.', ''))
                    ->sortable(),
                TextColumn::make('bdt_per_usd_poisha')
                    ->label('Rate BDT/USD')
                    ->formatStateUsing(fn ($state): string => number_format(((int) $state) / 100, 2, '.', ''))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
