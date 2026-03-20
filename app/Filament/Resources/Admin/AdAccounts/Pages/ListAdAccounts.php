<?php

namespace App\Filament\Resources\Admin\AdAccounts\Pages;

use App\Filament\Resources\Admin\AdAccounts\AdAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdAccounts extends ListRecords
{
    protected static string $resource = AdAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
