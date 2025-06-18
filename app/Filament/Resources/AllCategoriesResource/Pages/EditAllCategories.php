<?php

namespace App\Filament\Resources\AllCategoriesResource\Pages;

use App\Filament\Resources\AllCategoriesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAllCategories extends EditRecord
{
    protected static string $resource = AllCategoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
