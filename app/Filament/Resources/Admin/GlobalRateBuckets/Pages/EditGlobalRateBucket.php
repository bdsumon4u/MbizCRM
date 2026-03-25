<?php

namespace App\Filament\Resources\Admin\GlobalRateBuckets\Pages;

use App\Filament\Resources\Admin\GlobalRateBuckets\GlobalRateBucketResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGlobalRateBucket extends EditRecord
{
    protected static string $resource = GlobalRateBucketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
