<?php

namespace App\Filament\Resources\PublicationPriceResource\Pages;

use App\Filament\Resources\PublicationPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPublicationPrices extends ListRecords
{
    protected static string $resource = PublicationPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
