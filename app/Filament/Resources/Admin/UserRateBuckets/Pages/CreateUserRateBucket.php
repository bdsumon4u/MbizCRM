<?php

namespace App\Filament\Resources\Admin\UserRateBuckets\Pages;

use App\Filament\Resources\Admin\UserRateBuckets\UserRateBucketResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserRateBucket extends CreateRecord
{
    protected static string $resource = UserRateBucketResource::class;
}
