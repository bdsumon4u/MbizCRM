<?php

namespace App\Filament\Resources\Admin\Users\Tables;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('wallet.current_balance_poisha')
                    ->label('Wallet (BDT)')
                    ->formatStateUsing(fn ($state): string => number_format(((int) $state) / 100, 2))
                    ->default('0.00'),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('has_email_authentication')
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
                Action::make('top_up_wallet')
                    ->label('Top Up Wallet')
                    ->icon('heroicon-o-banknotes')
                    ->form([
                        TextInput::make('amount_bdt')
                            ->label('Amount (BDT)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        TextInput::make('payment_reference')
                            ->maxLength(255),
                        Textarea::make('note')
                            ->rows(3),
                    ])
                    ->action(function (User $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $amountPoisha = (int) round(((float) $data['amount_bdt']) * 100);

                            $wallet = Wallet::query()->firstOrCreate(
                                ['user_id' => $record->id],
                                [
                                    'currency' => 'BDT',
                                ]
                            );

                            $beforeBalancePoisha = (int) $wallet->current_balance_poisha;
                            $afterBalancePoisha = $beforeBalancePoisha + $amountPoisha;

                            $wallet->update([
                                'current_balance_poisha' => $afterBalancePoisha,
                                'lifetime_credit_poisha' => (int) $wallet->lifetime_credit_poisha + $amountPoisha,
                                'last_activity_at' => now(),
                            ]);

                            $admin = auth('admin')->user();

                            WalletTransaction::query()->create([
                                'wallet_id' => $wallet->id,
                                'user_id' => $record->id,
                                'performed_by_admin_id' => $admin?->id,
                                'type' => TransactionType::WALLET_TOP_UP,
                                'status' => TransactionStatus::COMPLETED,
                                'source' => TransactionSource::ADMIN_PANEL,
                                'amount_bdt_poisha' => $amountPoisha,
                                'balance_before_poisha' => $beforeBalancePoisha,
                                'balance_after_poisha' => $afterBalancePoisha,
                                'external_reference' => $data['payment_reference'] ?: null,
                                'metadata' => [
                                    'amount_bdt' => (float) $data['amount_bdt'],
                                    'note' => $data['note'] ?: null,
                                ],
                                'processed_at' => now(),
                            ]);
                        });

                        Notification::make()
                            ->title('Wallet topped up successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
