<?php

namespace App\Filament\Resources\PublicationPriceResource\Pages;

use App\Filament\Resources\PublicationPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPublicationPrice extends EditRecord
{
    protected static string $resource = PublicationPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
