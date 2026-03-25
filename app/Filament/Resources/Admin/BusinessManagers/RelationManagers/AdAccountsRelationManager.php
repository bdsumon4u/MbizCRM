<?php

namespace App\Filament\Resources\Admin\BusinessManagers\RelationManagers;

use App\Services\FacebookMarketingService;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AdAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'adAccounts';

    private ?Collection $allAdAccounts = null;

    private ?Collection $availableAdAccounts = null;

    public function form(Schema $schema): Schema
    {
        try {
            $this->allAdAccounts = FacebookMarketingService::create($this->getOwnerRecord()->access_token)
                ->getBusinessManagerAdAccounts($this->getOwnerRecord()->bm_id);

            $existingActIds = $this->getOwnerRecord()->adAccounts()
                ->pluck('act_id')
                ->toArray();

            $this->availableAdAccounts = $this->allAdAccounts->filter(fn (array $account): bool => ! in_array($account['act_id'], $existingActIds))
                ->mapWithKeys(fn (array $account): array => [$account['act_id'] => $account]);

            if ($this->availableAdAccounts->isEmpty()) {
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

        return $schema
            ->components([
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
                    ->visible(fn (Get $get): bool => $this->availableAdAccounts instanceof Collection && $this->availableAdAccounts->isNotEmpty()),

                CheckboxList::make('selected_ad_accounts')
                    ->label('Select Ad Accounts to Import')
                    ->options(function (Get $get, Set $set) {
                        if (! $this->availableAdAccounts instanceof Collection) {
                            return [];
                        }

                        return $this->availableAdAccounts->mapWithKeys(fn (array $account): array => [
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
                        if (! $this->availableAdAccounts instanceof Collection) {
                            return [];
                        }

                        return $this->availableAdAccounts->mapWithKeys(function (array $account): array {
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
                    ->visible(fn (Get $get): bool => $this->availableAdAccounts instanceof Collection && $this->availableAdAccounts->isNotEmpty())
                    ->searchable()
                    ->searchPrompt('Search ad accounts...')
                    ->required()
                    ->columns(1),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->action(function (array $data) {
                        foreach ($data['selected_ad_accounts'] ?? [] as $actId) {
                            $account = $this->availableAdAccounts->get($actId);
                            $this->getOwnerRecord()->adAccounts()->create(Arr::except($account, [
                                'bm_id',
                                'facebook_ad_account_id',
                                'created_time',
                                'disable_reason_description',
                                'spend_limit',
                                'amount_spent',
                                'funding_source_details',
                                'business_manager_id',
                                'business_id',
                                'last_sync_at',
                            ]));
                        }
                    }),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private function selectAllAdAccounts(Get $get, Set $set): void
    {
        if (! $this->availableAdAccounts instanceof Collection) {
            return;
        }

        $set('selected_ad_accounts', $this->availableAdAccounts->pluck('act_id')->toArray());
        $set('select_all', true);
    }

    private function deselectAllAdAccounts(Get $get, Set $set): void
    {
        $set('selected_ad_accounts', []);
        $set('select_all', false);
    }

    private function updateSelectAllState(Get $get, Set $set): void
    {
        if (! $this->availableAdAccounts instanceof Collection) {
            $set('select_all', false);

            return;
        }

        $selectedCount = count($get('selected_ad_accounts') ?? []);
        $totalCount = $this->availableAdAccounts->count();

        $set('select_all', $selectedCount === $totalCount && $totalCount > 0);
    }
}
