<?php

namespace App\Filament\Resources\Admin\BusinessManagers\Pages;

use App\Filament\Resources\Admin\BusinessManagers\BusinessManagerResource;
use App\Filament\Resources\Admin\BusinessManagers\Schemas\BusinessManagerForm;
use App\Services\FacebookMarketingService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class ListBusinessManagers extends ListRecords
{
    protected static string $resource = BusinessManagerResource::class;

    public function mount(): void
    {
        parent::mount();

        if (session()->has('facebook_oauth_success')) {
            Notification::make()
                ->title('Facebook Connected')
                ->body((string) session('facebook_oauth_success'))
                ->success()
                ->send();
        }

        if (session()->has('facebook_oauth_error')) {
            Notification::make()
                ->title('Facebook OAuth Failed')
                ->body((string) session('facebook_oauth_error'))
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect_facebook_oauth')
                ->label('Connect Facebook (OAuth)')
                ->icon('heroicon-o-link')
                ->url(Route::has('admin.facebook.business-managers.oauth.redirect')
                    ? route('admin.facebook.business-managers.oauth.redirect')
                    : null)
                ->openUrlInNewTab()
                ->disabled(fn (): bool => ! Route::has('admin.facebook.business-managers.oauth.redirect'))
                ->tooltip(fn (): ?string => Route::has('admin.facebook.business-managers.oauth.redirect')
                    ? null
                    : 'OAuth routes are not available. Clear route cache or redeploy.'),
            Action::make('import')
                ->form([
                    TextInput::make('access_token')
                        ->password()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $service = FacebookMarketingService::create($data['access_token']);
                    $result = $service->getAllBusinessManagers();

                    Notification::make()
                        ->title('Import request completed')
                        ->body('Discovered '.($result['total_businesses'] ?? 0).' business managers.')
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->slideOver()
                ->steps([
                    BusinessManagerForm::accessTab(),
                    BusinessManagerForm::detailsTab(),
                    BusinessManagerForm::importTab(),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        $businessManager = static::getResource()::getModel()::create([
                            'bm_id' => $data['bm_id'],
                            'name' => $data['name'],
                            'access_token' => $data['access_token'],
                            'ad_account_prefix' => $data['ad_account_prefix'],
                            'description' => $data['description'],
                            // 'status' => $data['status'],
                            // 'currency' => $data['currency'],
                            // 'balance' => $data['balance'],
                            'synced_at' => now(),
                        ]);

                        foreach ($data['selected_ad_accounts'] ?? [] as $actId) {
                            $account = $data['available_ad_accounts'][$actId];
                            $businessManager->adAccounts()->create(Arr::except($account, [
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
                    });
                }),
        ];
    }
}
