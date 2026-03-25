<?php

namespace App\Filament\Resources\Admin\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                Textarea::make('app_authentication_secret')
                    ->columnSpanFull(),
                Textarea::make('app_authentication_recovery_codes')
                    ->columnSpanFull(),
                Toggle::make('has_email_authentication')
                    ->required(),
            ]);
    }
}
