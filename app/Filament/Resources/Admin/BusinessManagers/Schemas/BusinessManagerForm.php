<?php

namespace App\Filament\Resources\Admin\BusinessManagers\Schemas;

use App\Models\AdAccount;
use App\Services\FacebookMarketingService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Collection;

class BusinessManagerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bm_id')
                    ->label('Business Manager ID')
                    ->required(),
                TextInput::make('ad_account_prefix')
                    ->label('Ad Account Prefix'),
                TextInput::make('access_token')
                    ->required()
                    ->password(),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required(),
                TextInput::make('currency')
                    ->required()
                    ->default('USD'),
                TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('synced_at'),
            ]);
    }

    public static function accessTab(): Step
    {
        return Step::make('Access')->schema([
            TextInput::make('bm_id')
                ->label('Business Manager ID')
                ->required(),
            Textarea::make('access_token')
                ->label('Access Token')
                ->required()
                ->rows(3),
        ])
            ->afterValidation(function (Get $get, Set $set) {
                try {
                    $data = FacebookMarketingService::create($get('access_token'))
                        ->getBusinessManagerDetails($get('bm_id'));

                    $set('name', $data['name'] ?? '');
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Invalid Access Token or Business Manager ID')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    throw new Halt;
                }
            });
    }

    public static function detailsTab(): Step
    {
        return Step::make('Details')->schema([
            TextInput::make('name')
                ->readOnly()
                ->required(),
            TextInput::make('ad_account_prefix')
                ->label('Ad Account Prefix'),
            Textarea::make('description')
                ->columnSpanFull(),
        ])
            ->afterValidation(function (Get $get, Set $set) {
                try {
                    $allAdAccounts = FacebookMarketingService::create($get('access_token'))
                        ->getBusinessManagerAdAccounts($get('bm_id'));

                    $existingActIds = AdAccount::query()
                        ->whereHas('businessManager', function ($query) use ($get): void {
                            $query->where('bm_id', $get('bm_id'));
                        })
                        ->pluck('act_id')
                        ->toArray();

                    $availableAdAccounts = $allAdAccounts->filter(fn (array $account): bool => ! in_array($account['act_id'], $existingActIds))
                        ->mapWithKeys(fn (array $account): array => [$account['act_id'] => $account]);

                    $set('available_ad_accounts', $availableAdAccounts);

                    if ($get('available_ad_accounts')->isEmpty()) {
                        Notification::make()
                            ->title('No new ad accounts found')
                            ->body('All ad accounts from this business manager have already been imported.')
                            ->info()
                            ->send();
                    }
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error Fetching Ad Accounts')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    throw new Halt;
                }
            });
    }

    public static function importTab(): Step
    {
        return Step::make('Import')->schema([
            Hidden::make('available_ad_accounts'),
            Checkbox::make('select_all')
                ->label('Select All Ad Accounts')
                ->live()
                ->afterStateUpdated(function (bool $state, Get $get, Set $set): void {
                    if ($state) {
                        self::selectAllAdAccounts($get, $set);
                    } else {
                        self::deselectAllAdAccounts($get, $set);
                    }
                })
                ->visible(fn (Get $get): bool => $get('available_ad_accounts') instanceof Collection && $get('available_ad_accounts')->isNotEmpty()),

            CheckboxList::make('selected_ad_accounts')
                ->label('Select Ad Accounts to Import')
                ->options(function (Get $get, Set $set) {
                    if (! $get('available_ad_accounts') instanceof Collection) {
                        return [];
                    }

                    return $get('available_ad_accounts')->mapWithKeys(fn (array $account): array => [
                        $account['act_id'] => sprintf(
                            '%s (%s) - %s %s - %s',
                            $account['name'],
                            $account['act_id'],
                            number_format($account['balance'], 2),
                            $account['currency'],
                            $account['status']->getLabel()
                        ),
                    ])->toArray();
                })
                ->descriptions(function (Get $get, Set $set) {
                    if (! $get('available_ad_accounts') instanceof Collection) {
                        return [];
                    }

                    return $get('available_ad_accounts')->mapWithKeys(function (array $account): array {
                        $status = $account['status']->getLabel();
                        $timezone = $account['timezone'] ?? 'N/A';
                        $balance = number_format($account['balance'], 2);

                        return [
                            $account['act_id'] => "Status: {$status} | Balance: {$balance} {$account['currency']} | Timezone: {$timezone}",
                        ];
                    })->toArray();
                })
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set): void {
                    self::updateSelectAllState($get, $set);
                })
                ->visible(fn (Get $get): bool => $get('available_ad_accounts') instanceof Collection && $get('available_ad_accounts')->isNotEmpty())
                ->searchable()
                ->searchPrompt('Search ad accounts...')
                ->required()
                ->columns(1),
        ]);
    }

    private static function selectAllAdAccounts(Get $get, Set $set): void
    {
        if (! $get('available_ad_accounts') instanceof Collection) {
            return;
        }

        $set('selected_ad_accounts', $get('available_ad_accounts')->pluck('act_id')->toArray());
        $set('select_all', true);
    }

    private static function deselectAllAdAccounts(Get $get, Set $set): void
    {
        $set('selected_ad_accounts', []);
        $set('select_all', false);
    }

    private static function updateSelectAllState(Get $get, Set $set): void
    {
        if (! $get('available_ad_accounts') instanceof Collection) {
            $set('select_all', false);

            return;
        }

        $selectedCount = count($get('selected_ad_accounts') ?? []);
        $totalCount = $get('available_ad_accounts')->count();

        $set('select_all', $selectedCount === $totalCount && $totalCount > 0);
    }
}
