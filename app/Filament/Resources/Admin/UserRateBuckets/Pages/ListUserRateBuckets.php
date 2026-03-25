<?php

namespace App\Filament\Resources\Admin\UserRateBuckets\Pages;

use App\Filament\Resources\Admin\UserRateBuckets\UserRateBucketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserRateBuckets extends ListRecords
{
    protected static string $resource = UserRateBucketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
