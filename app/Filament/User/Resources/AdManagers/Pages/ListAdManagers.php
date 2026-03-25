<?php

namespace App\Filament\User\Resources\AdManagers\Pages;

use App\Filament\User\Resources\AdManagers\AdManagerResource;
use Filament\Resources\Pages\ListRecords;

class ListAdManagers extends ListRecords
{
    protected static string $resource = AdManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
