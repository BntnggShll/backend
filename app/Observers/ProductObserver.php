<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class ProductObserver
{
    public function saved(Product $product): void
    {
        try {
            $productUnits = $product->productUnits()->orderBy('id')->get();
    
            Notification::make()
                ->title('Observer Dipanggil')
                ->body("Memproses {$productUnits->count()} unit.")
                ->success()
                ->send();
    
            $previousUnitId = null;
            foreach ($productUnits as $unit) {
                if ($previousUnitId) {
                    $unit->parent_id = $previousUnitId;
                    $unit->saveQuietly();
    
                    Notification::make()
                        ->title("Parent Di-set")
                        ->body("Unit {$unit->id} → parent {$previousUnitId}")
                        ->info()
                        ->send();
                }
                $previousUnitId = $unit->id;
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error di ProductObserver')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
