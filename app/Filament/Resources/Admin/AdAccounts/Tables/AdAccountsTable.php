<?php

namespace App\Filament\Resources\Admin\AdAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bm_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('act_id')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('balance')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('daily_budget')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lifetime_budget')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('spent_today')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('spent_yesterday')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('spent_this_month')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('spent_last_month')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->searchable(),
                TextColumn::make('card_last_four')
                    ->searchable(),
                TextColumn::make('card_brand')
                    ->searchable(),
                TextColumn::make('card_expiry')
                    ->date()
                    ->sortable(),
                TextColumn::make('billing_address_country')
                    ->searchable(),
                TextColumn::make('spend_cap')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('daily_spend_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lifetime_spend_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('impressions_today')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('clicks_today')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('conversions_today')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ctr_today')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cpc_today')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('timezone')
                    ->searchable(),
                TextColumn::make('account_type')
                    ->searchable(),
                TextColumn::make('synced_at')
                    ->dateTime()
                    ->sortable(),
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
