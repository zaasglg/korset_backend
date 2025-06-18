<?php

namespace App\Filament\Resources\ProductParameterValueResource\Pages;

use App\Filament\Resources\ProductParameterValueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductParameterValue extends EditRecord
{
    protected static string $resource = ProductParameterValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
