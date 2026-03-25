<?php

namespace App\Filament\Resources\Admin\AdAccounts\Tables;

use App\Models\AdAccount;
use App\Services\AdAccountFundingService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class AdAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('businessManager.name')
                    ->label('Business Manager')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Assigned User')
                    ->placeholder('Unassigned')
                    ->searchable()
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
                Action::make('fund_from_wallet')
                    ->label('Fund')
                    ->icon('heroicon-o-banknotes')
                    ->form([
                        TextInput::make('requested_usd')
                            ->label('Requested USD')
                            ->required()
                            ->numeric()
                            ->step('0.000001')
                            ->minValue(0.000001),
                    ])
                    ->action(function (AdAccount $record, array $data): void {
                        if ($record->user === null) {
                            throw ValidationException::withMessages([
                                'requested_usd' => 'Assign a user to this ad account before funding.',
                            ]);
                        }

                        $requestedUsdMicros = (int) round(((float) $data['requested_usd']) * 1_000_000);
                        $fundingService = app(AdAccountFundingService::class);

                        $result = $fundingService->commit(
                            $record->user,
                            $record,
                            $requestedUsdMicros,
                            'admin-'.$record->id.'-'.now()->format('YmdHisv'),
                        );

                        $notification = Notification::make()
                            ->title($result['success'] ? 'Funding completed' : 'Funding failed')
                            ->body($result['message']);

                        if ($result['success']) {
                            $notification->success();
                        } else {
                            $notification->danger();
                        }

                        $notification->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
