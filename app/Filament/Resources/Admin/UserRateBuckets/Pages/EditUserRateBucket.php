<?php

namespace App\Filament\Resources\Admin\UserRateBuckets\Pages;

use App\Filament\Resources\Admin\UserRateBuckets\UserRateBucketResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUserRateBucket extends EditRecord
{
    protected static string $resource = UserRateBucketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
