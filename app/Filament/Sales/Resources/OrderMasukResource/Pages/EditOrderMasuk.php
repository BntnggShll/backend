<?php

namespace App\Filament\Sales\Resources\OrderMasukResource\Pages;

use App\Filament\Sales\Resources\OrderMasukResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrderMasuk extends EditRecord
{
    protected static string $resource = OrderMasukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
