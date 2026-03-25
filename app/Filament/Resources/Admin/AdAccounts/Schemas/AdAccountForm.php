<?php

namespace App\Filament\Resources\Admin\AdAccounts\Schemas;

use App\Enums\AdAccountStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AdAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->preload(),
                Select::make('business_manager_id')
                    ->relationship('businessManager', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('act_id')
                    ->required(),
                Select::make('status')
                    ->options(AdAccountStatus::getOptions())
                    ->required()
                    ->default((string) AdAccountStatus::ACTIVE->value),
                TextInput::make('currency')
                    ->required()
                    ->default('USD'),
                TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('daily_budget')
                    ->numeric(),
                TextInput::make('lifetime_budget')
                    ->numeric(),
                TextInput::make('spent_today')
                    ->numeric(),
                TextInput::make('spent_yesterday')
                    ->numeric(),
                TextInput::make('spent_this_month')
                    ->numeric(),
                TextInput::make('spent_last_month')
                    ->numeric(),
                TextInput::make('payment_method'),
                TextInput::make('card_last_four'),
                TextInput::make('card_brand'),
                DatePicker::make('card_expiry'),
                TextInput::make('billing_address_country'),
                TextInput::make('spend_cap')
                    ->numeric(),
                TextInput::make('daily_spend_limit')
                    ->numeric(),
                TextInput::make('lifetime_spend_limit')
                    ->numeric(),
                TextInput::make('impressions_today')
                    ->numeric(),
                TextInput::make('clicks_today')
                    ->numeric(),
                TextInput::make('conversions_today')
                    ->numeric(),
                TextInput::make('ctr_today')
                    ->numeric(),
                TextInput::make('cpc_today')
                    ->numeric(),
                TextInput::make('timezone'),
                TextInput::make('account_type')
                    ->required()
                    ->default('business'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('disable_reason'),
                DateTimePicker::make('synced_at'),
            ]);
    }
}
