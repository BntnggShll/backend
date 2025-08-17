<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSales extends Model
{
    use HasFactory;
    protected $table = 'products';
    protected $fillable = ['nama_produk', 'jenis_produk', 'image'];

    public function productUnits()
    {
        return $this->hasMany(ProductUnit::class,'product_id');
    }
    public function orderItems()
    {
        return $this->hasManyThrough(
            OrderItem::class,
            ProductUnit::class,
            'product_id',       // FK di product_units
            'product_unit_id',  // FK di order_items
            'id',               // PK di products
            'id'                // PK di product_units
        );
    }

    public function salesStocks()
{
    return $this->hasManyThrough(
        sales_stocks::class,
        ProductUnit::class,
        'product_id',      // FK di product_units
        'product_unit_id', // FK di sales_stocks
        'id',              // PK di products
        'id'               // PK di product_units
    );
}

public function calculateSalesStockStatus(): array
{
    $baseUnit = $this->productUnits()->where('is_base_unit', true)->first();
    if (!$baseUnit) {
        return [
            'total_in'  => 0,
            'total_out' => 0,
            'net'       => 0,
            'display'   => 'Satuan dasar belum di-set',
            'conversion_factors' => []
        ];
    }

    // 1️⃣ Hitung total IN dan OUT dari salesStocks
    $in  = $this->salesStocks()->where('status', 'in')->sum('quantity');
    $out = $this->salesStocks()->where('status', 'out')->sum('quantity');
    $net = $in - $out;

    // 2️⃣ Buat peta faktor konversi
    $conversionFactors = [];
    $calculateFactor = function ($unit) use (&$calculateFactor, &$conversionFactors) {
        if (isset($conversionFactors[$unit->id])) {
            return $conversionFactors[$unit->id];
        }
        if ($unit->is_base_unit) {
            return $conversionFactors[$unit->id] = 1;
        }
        $childUnit = $unit->children->first();
        if (!$childUnit) {
            return $conversionFactors[$unit->id] = 0;
        }
        return $conversionFactors[$unit->id] =
            $childUnit->conversion_rate * $calculateFactor($childUnit);
    };
    foreach ($this->productUnits as $unit) {
        $calculateFactor($unit);
    }

    // 3️⃣ Konversi NET stok (dalam base unit) ke tampilan multi-unit
    $displayString   = '';
    $remainingStock  = $net;
    $sortedUnits = $this->productUnits->sortByDesc(function ($unit) use ($conversionFactors) {
        return $conversionFactors[$unit->id] ?? 0;
    });

    foreach ($sortedUnits as $unit) {
        $factor = $conversionFactors[$unit->id] ?? 0;
        if ($factor <= 0) continue;

        if ($remainingStock >= $factor) {
            $count = floor($remainingStock / $factor);
            $displayString .= $count . ' ' . $unit->unit->nama_unit . ', ';
            $remainingStock -= $count * $factor;
        }
    }
    $displayString = rtrim($displayString, ', ');

    return [
        'total_in'  => $in,
        'total_out' => $out,
        'net'       => $net,
        'display'   => $displayString ?: null,
        'conversion_factors' => $conversionFactors
    ];
}



}
