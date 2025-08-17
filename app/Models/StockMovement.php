<?php

namespace App\Models;

use App\Models\sales_stocks;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class StockMovement extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'product_unit_id',
        'quantity',
        'type',
        'sales_id',
        
    ];

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }
    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }
    public function stock_sales()
    {
        return $this->belongsTo(sales_stocks::class, 'stock_movement_id');
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

        $validationCallback = function (StockMovement $stockMovement) {
            if ($stockMovement->type === 'out') {
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
                $currentStockBase = optional($baseUnit->inventory)->quantity ?? 0;

                // Jika update data lama, kembalikan stok lama
                if ($stockMovement->exists) {
                    $originalQtyBase = abs($stockMovement->getOriginal('quantity'))
                        * self::getConversionToBase($productUnit);
                    $currentStockBase += $originalQtyBase;
                }

                // Cek stok
                if ($requestedQty > $currentStockBase) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Stok tidak mencukupi. Tersedia: '
                            . $currentStockBase . ' ' . $baseUnit->unit->nama_unit
                            . ', Diminta: ' . $requestedQty . ' ' . $baseUnit->unit->nama_unit
                    ]);
                }
            }
        };



        static::creating($validationCallback);
        static::updating($validationCallback);
        static::saved(function (StockMovement $stockMovement) {
            $existingSale = sales_stocks::where('stock_movement_id', $stockMovement->id)->first();

            if ($stockMovement->type === 'out' && !is_null($stockMovement->sales_id)) {
                $saleData = [
                    'sales_id' => $stockMovement->sales_id,
                    'product_unit_id' => $stockMovement->product_unit_id,
                    'quantity' => abs($stockMovement->quantity),
                    'status' => 'in',
                ];

                sales_stocks::updateOrCreate(
                    ['stock_movement_id' => $stockMovement->id],
                    $saleData
                );
            } else {
                if ($existingSale) {
                    $existingSale->delete();
                }
            }
            if ($stockMovement->relationLoaded('productUnit')) {
                self::updateInventoryFor($stockMovement->productUnit);
            } else {
                self::updateInventoryFor($stockMovement->load('productUnit')->productUnit);
            }
        });

        $updateInventoryCallback = function (StockMovement $stockMovement) {
            self::updateInventoryFor($stockMovement->productUnit);
        };

        static::deleted($updateInventoryCallback);
    }

    public static function updateInventoryFor(ProductUnit $productUnit)
    {
        if (!$productUnit)
            return;
        $lastMovement = StockMovement::where('product_unit_id', $productUnit->id)
            ->latest()
            ->first();
        if ($lastMovement) {
            $quantity = $lastMovement->quantity;
            $ids = $lastMovement->productUnit->id;
            // 
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
            ['quantity' => 0]
        );

        $inventory->quantity += $finalQuantity;
        $inventory->save();

    }
    private static function checkMinStockAndNotify(Inventory $inventory)
    {
        $productUnit = $inventory->productUnit;
        $product = $productUnit->product;
        $baseUnit = $product->productUnits()->where('is_base_unit', true)->first();
        if (!$baseUnit || $baseUnit->min_stock_level <= 0) {
            return;
        }
        $stockInfo = $product->calculateStock();
        $currentTotalInBase = $stockInfo['total_in_base'];

        $minLevel = $baseUnit->min_stock_level;

        $movedQuantity = abs($inventory->quantity);
        $conversionFactor = $stockInfo['conversion_factors'][$inventory->product_unit_id] ?? 0;
        $movedQuantityInBase = $movedQuantity * $conversionFactor;

        $oldTotalInBase = $currentTotalInBase + $movedQuantityInBase;

        if ($currentTotalInBase <= $minLevel && $oldTotalInBase > $minLevel) {

            $recipients = User::where('role', 'admin')->get();
            if ($recipients->isEmpty()) {
                return;
            }

            $productName = $product->nama_produk;
            $baseUnitName = $baseUnit->unit->nama_unit;
            Notification::make()
                ->title('Peringatan Stok Minimum')
                ->body("Stok total untuk {$productName} telah mencapai batas minimum. Sisa stok: {$currentTotalInBase} {$baseUnitName}.")
                ->warning()
                ->sendToDatabase($recipients);
        }
    }
}