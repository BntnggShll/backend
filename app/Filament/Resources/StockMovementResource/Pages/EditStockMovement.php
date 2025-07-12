<?php

namespace App\Filament\Resources\StockMovementResource\Pages;

use App\Filament\Resources\StockMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockMovement extends EditRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Logika yang sama persis seperti di halaman Create
        if (isset($data['type']) && $data['type'] === 'out' && isset($data['quantity'])) {
            $data['quantity'] = -abs((int)$data['quantity']);
        } else if (isset($data['quantity'])) {
             $data['quantity'] = abs((int)$data['quantity']);
        }

        return $data;
    }
}
