<?php

namespace App\Filament\Resources\ProductParameterResource\Pages;

use App\Filament\Resources\ProductParameterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductParameter extends EditRecord
{
    protected static string $resource = ProductParameterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
