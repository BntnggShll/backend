<?php

namespace App\Filament\Sales\Resources\SalesStockResource\Pages;

use App\Filament\Sales\Resources\SalesStockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesStocks extends ListRecords
{
    protected static string $resource = SalesStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
