<?php

namespace App\Filament\Resources\Admin\BusinessManagers\Pages;

use App\Filament\Resources\Admin\BusinessManagers\BusinessManagerResource;
use App\Filament\Resources\Admin\BusinessManagers\Schemas\BusinessManagerForm;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessManager extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = BusinessManagerResource::class;

    public function getSteps(): array
    {
        return [
            BusinessManagerForm::accessTab(),
            BusinessManagerForm::detailsTab(),
        ];
    }
}
