<?php

namespace App\Filament\Resources\Admin\BusinessManagers\Pages;

use App\Filament\Resources\Admin\BusinessManagers\BusinessManagerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBusinessManager extends EditRecord
{
    protected static string $resource = BusinessManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
