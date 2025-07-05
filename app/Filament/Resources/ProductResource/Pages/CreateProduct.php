<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected function afterCreate(): void
    {
        $previousUnitId = null;

        // $this->record adalah record Product yang baru saja dibuat
        $productUnits = $this->record->productUnits()->orderBy('id', 'asc')->get();

        foreach ($productUnits as $unit) {
            if ($previousUnitId) {
                $unit->parent_id = $previousUnitId;
                // Kita tidak perlu saveQuietly() di sini karena ini bukan observer
                $unit->save(); 
            }
            $previousUnitId = $unit->id;
        }
    }
}
