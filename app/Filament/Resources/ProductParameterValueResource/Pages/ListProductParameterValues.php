<?php

namespace App\Filament\Resources\ProductParameterValueResource\Pages;

use App\Filament\Resources\ProductParameterValueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductParameterValues extends ListRecords
{
    protected static string $resource = ProductParameterValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
