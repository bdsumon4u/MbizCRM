<?php

namespace App\Filament\Resources\Admin\GlobalRateBuckets\Pages;

use App\Filament\Resources\Admin\GlobalRateBuckets\GlobalRateBucketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGlobalRateBuckets extends ListRecords
{
    protected static string $resource = GlobalRateBucketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
