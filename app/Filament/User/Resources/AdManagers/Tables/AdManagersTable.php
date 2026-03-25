<?php

namespace App\Filament\User\Resources\AdManagers\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AdManagersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Ad Manager')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bm_id')
                    ->label('Manager ID')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::of($state)->replace('_', ' ')->title()->toString())
                    ->color(function (string $state): string {
                        return match ($state) {
                            'active' => 'success',
                            'pending_verification', 'restricted' => 'warning',
                            'inactive', 'archived' => 'gray',
                            default => 'danger',
                        };
                    }),
                TextColumn::make('currency')
                    ->sortable(),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('assigned_ad_accounts_count')
                    ->label('Assigned Accounts')
                    ->sortable(),
                TextColumn::make('synced_at')
                    ->label('Last Synced')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                        'pending_verification' => 'Pending Verification',
                        'restricted' => 'Restricted',
                        'disabled' => 'Disabled',
                        'archived' => 'Archived',
                    ]),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
