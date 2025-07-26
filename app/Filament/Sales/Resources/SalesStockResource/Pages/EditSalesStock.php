<?php

namespace App\Filament\Sales\Resources\SalesStockResource\Pages;

use App\Filament\Sales\Resources\SalesStockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesStock extends EditRecord
{
    protected static string $resource = SalesStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
