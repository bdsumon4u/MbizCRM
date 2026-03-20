<?php

namespace App\Filament\Resources\Admin\BusinessManagers\Pages;

use App\Filament\Resources\Admin\BusinessManagers\BusinessManagerResource;
use App\Filament\Resources\Admin\BusinessManagers\Schemas\BusinessManagerForm;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ListBusinessManagers extends ListRecords
{
    protected static string $resource = BusinessManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
