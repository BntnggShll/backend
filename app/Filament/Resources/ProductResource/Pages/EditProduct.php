<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function afterSave(): void
    {
        $previousUnitId = null;

        // $this->record adalah record Product yang baru saja di-update
        $productUnits = $this->record->productUnits()->orderBy('id', 'asc')->get();

        foreach ($productUnits as $unit) {
            // Set semua parent_id jadi null dulu untuk reset,
            // ini penting jika urutan di repeater diubah saat edit.
            $unit->parent_id = null;
            $unit->save();
        }

        // Jalankan lagi logika pengurutan
        foreach ($productUnits->fresh() as $unit) { // ->fresh() untuk mengambil data terbaru setelah di-reset
             if ($previousUnitId) {
                $unit->parent_id = $previousUnitId;
                $unit->save();
            }
            $previousUnitId = $unit->id;
        }
    }
}
