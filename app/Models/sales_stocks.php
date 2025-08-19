<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class sales_stocks extends Model
{
    use HasFactory;

    protected $table = 'sales_stocks';
    protected $fillable = ['sales_id', 'quantity', 'status', 'product_unit_id', 'stock_movement_id'];

    public function sales()
    {
        return $this->belongsTo(User::class);
    }
    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }
    public function stockmovement()
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }
    protected static function getConversionToBase($productUnit)
    {
        $multiplier = 1;

        while ($productUnit && !$productUnit->is_base_unit) {
            $child = $productUnit->children()->first();
            if (!$child) {
                break;
            }

            $multiplier *= ($child->conversion_rate ?: 1);
            $productUnit = $child;
        }

        return $multiplier;
    }

    protected static function boot()
    {
        parent::boot();

        $validationCallback = function (sales_stocks $stockMovement) {
            if ($stockMovement->status === 'out') {
                $productUnit = ProductUnit::with('inventory', 'unit')
                    ->find($stockMovement->product_unit_id);
                if (!$productUnit) {
                    throw ValidationException::withMessages([
                        'product_unit_id' => 'Unit produk tidak ditemukan.',
                    ]);
                }
                $baseUnit = ProductUnit::where('product_id', $productUnit->product_id)
                    ->where('is_base_unit', true)
                    ->with('inventory', 'unit')
                    ->first();
                if (!$baseUnit) {
                    throw ValidationException::withMessages([
                        'product_unit_id' => 'Base unit untuk produk ini tidak ditemukan.',
                    ]);
                }

                $requestedQty = abs($stockMovement->quantity);

                if (!$productUnit->is_base_unit) {
                    $requestedQty *= self::getConversionToBase($productUnit);
                }


                // Stok di base unit
                $currentStockBase = optional($baseUnit->inventory)->quantity_sales ?? 0;

                // Jika update data lama, kembalikan stok lama
                if ($stockMovement->exists) {
                    $originalQtyBase = abs($stockMovement->getOriginal('quantity_sales'))
                        * self::getConversionToBase($productUnit);
                    $currentStockBase += $originalQtyBase;
                }

                // Cek stok
                if ($requestedQty > $currentStockBase) {
                    throw ValidationException::withMessages([
                        'quantity_sales' => 'Stok tidak mencukupi. Tersedia: '
                            . $currentStockBase . ' ' . $baseUnit->unit->nama_unit
                            . ', Diminta: ' . $requestedQty . ' ' . $baseUnit->unit->nama_unit
                    ]);
                }
            }
        };



        static::creating($validationCallback);
        static::updating($validationCallback);
        static::saved(function (sales_stocks $stockMovement) {

            if ($stockMovement->relationLoaded('productUnit')) {
                self::updateInventoryFor($stockMovement->productUnit);
            } else {
                self::updateInventoryFor($stockMovement->load('productUnit')->productUnit);
            }
        });

        $updateInventoryCallback = function (sales_stocks $stockMovement) {
            self::updateInventoryFor($stockMovement->productUnit);
        };

        static::deleted($updateInventoryCallback);
    }

    public static function updateInventoryFor(ProductUnit $productUnit)
    {
        if (!$productUnit)
            return;
        $lastMovement = sales_stocks::where('product_unit_id', $productUnit->id)
            ->latest()
            ->first();
        if ($lastMovement) {
            $quantity = $lastMovement->quantity;
            $movementType = $lastMovement->status;
            $ids = $lastMovement->productUnit->id;
        }
        $finalQuantity = 0;
        if ($productUnit->is_base_unit === false) {
            $childUnit = $productUnit->children->first();
            if ($childUnit) {
                $conversionRate = $childUnit->conversion_rate;
                $id = $childUnit->id;
                $finalQuantity = $quantity * $conversionRate;
            }
        } else {
            $finalQuantity = $quantity;
            $id = $ids;
        }
        $inventory = Inventory::firstOrCreate(
            ['product_unit_id' => $id],
            ['quantity_sales' => 0]
        );

        if ($movementType === 'out') {
            $inventory->quantity_sales -= abs($finalQuantity);
        } else {
            $inventory->quantity_sales += abs($finalQuantity);
        }
        $inventory->save();

    }
}
