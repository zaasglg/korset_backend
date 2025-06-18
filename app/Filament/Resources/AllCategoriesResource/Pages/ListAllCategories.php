<?php

namespace App\Filament\Resources\AllCategoriesResource\Pages;

use App\Filament\Resources\AllCategoriesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAllCategories extends ListRecords
{
    protected static string $resource = AllCategoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
