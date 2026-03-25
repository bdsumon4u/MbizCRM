<?php

namespace App\Filament\User\Resources\WalletTransactions\Tables;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (TransactionType|string $state): string => $state instanceof TransactionType ? $state->value : (string) $state)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(function (TransactionStatus|string $state): string {
                        $status = $state instanceof TransactionStatus ? $state : TransactionStatus::from((string) $state);

                        return match ($status) {
                            TransactionStatus::COMPLETED => 'success',
                            TransactionStatus::PENDING => 'warning',
                            TransactionStatus::FAILED, TransactionStatus::CANCELLED => 'danger',
                        };
                    })
                    ->formatStateUsing(fn (TransactionStatus|string $state): string => $state instanceof TransactionStatus ? $state->value : (string) $state)
                    ->sortable(),
                TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(fn (TransactionSource|string $state): string => $state instanceof TransactionSource ? $state->value : (string) $state),
                TextColumn::make('adAccount.act_id')
                    ->label('Ad Account')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('amount_bdt_poisha')
                    ->label('Amount (BDT)')
                    ->formatStateUsing(function (int|string $state): string {
                        $value = ((int) $state) / 100;

                        return number_format($value, 2);
                    })
                    ->sortable(),
                TextColumn::make('balance_after_poisha')
                    ->label('Balance After (BDT)')
                    ->formatStateUsing(fn (int|string $state): string => number_format(((int) $state) / 100, 2))
                    ->sortable(),
                TextColumn::make('external_reference')
                    ->label('Reference')
                    ->placeholder('-')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        TransactionStatus::PENDING->value => 'pending',
                        TransactionStatus::COMPLETED->value => 'completed',
                        TransactionStatus::FAILED->value => 'failed',
                        TransactionStatus::CANCELLED->value => 'cancelled',
                    ]),
                SelectFilter::make('type')
                    ->options([
                        TransactionType::WALLET_TOP_UP->value => 'wallet_top_up',
                        TransactionType::AD_ACCOUNT_FUNDING->value => 'ad_account_funding',
                        TransactionType::ADJUSTMENT->value => 'adjustment',
                        TransactionType::REFUND->value => 'refund',
                    ]),
                SelectFilter::make('source')
                    ->options([
                        TransactionSource::ADMIN_PANEL->value => 'admin_panel',
                        TransactionSource::USER_PANEL->value => 'user_panel',
                        TransactionSource::SYSTEM->value => 'system',
                    ]),
                SelectFilter::make('ad_account_id')
                    ->label('Ad Account')
                    ->relationship('adAccount', 'act_id')
                    ->searchable()
                    ->preload(),
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '<=', $date));
                    }),
                Filter::make('amount_range')
                    ->form([
                        TextInput::make('min_amount_bdt')
                            ->label('Min Amount (BDT)')
                            ->numeric(),
                        TextInput::make('max_amount_bdt')
                            ->label('Max Amount (BDT)')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min_amount_bdt'] ?? null, function (Builder $builder, string $value): Builder {
                                return $builder->whereRaw('ABS(amount_bdt_poisha) >= ?', [(int) round(((float) $value) * 100)]);
                            })
                            ->when($data['max_amount_bdt'] ?? null, function (Builder $builder, string $value): Builder {
                                return $builder->whereRaw('ABS(amount_bdt_poisha) <= ?', [(int) round(((float) $value) * 100)]);
                            });
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
