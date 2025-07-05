<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{

    use HasFactory;
    protected $table = 'products';
    protected $fillable = ['nama_produk', 'jenis_produk','image'];

    public function orderItems() {
        return $this->hasMany(OrderItem::class);
    }
    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function calculateStock(): array
{
    // 1. Temukan satuan dasar (base unit) sebagai patokan.
    $baseUnit = $this->productUnits()->where('is_base_unit', true)->first();

    if (!$baseUnit) {
        return ['total_in_base' => 0, 'display' => 'Satuan dasar belum di-set'];
    }

    // 2. Buat Peta Faktor Konversi untuk setiap unit ke satuan dasar.
    // Peta ini akan berisi: [product_unit_id => faktor_konversi_ke_satuan_dasar]
    // Contoh: jika 1 Dus = 120 Tablet, maka faktor konversi Dus adalah 120.
    $conversionFactors = [];
    $calculateFactor = function ($unit) use (&$calculateFactor, &$conversionFactors) {
        // Jika sudah dihitung, langsung kembalikan.
        if (isset($conversionFactors[$unit->id])) {
            return $conversionFactors[$unit->id];
        }
        // Satuan dasar adalah patokan, faktornya 1.
        if ($unit->is_base_unit) {
            return $conversionFactors[$unit->id] = 1;
        }
        // Cari unit anak untuk perhitungan.
        $childUnit = $this->productUnits->where('parent_id', $unit->id)->first();
        if (!$childUnit) {
            return $conversionFactors[$unit->id] = 0; // Seharusnya tidak terjadi jika setup benar
        }
        // Faktor konversi adalah (rate anak) * (faktor konversi anak ke satuan dasar).
        return $conversionFactors[$unit->id] = $childUnit->conversion_rate * $calculateFactor($childUnit);
    };

    foreach ($this->productUnits as $unit) {
        $calculateFactor($unit);
    }

    // 3. Ambil semua pergerakan stok dan hitung total dalam satuan dasar.
    $totalInBase = 0;
    $movements = StockMovement::whereIn('product_unit_id', $this->productUnits->pluck('id'))->get();

    foreach ($movements as $movement) {
        $factor = $conversionFactors[$movement->product_unit_id] ?? 0;
        if ($factor > 0) {
            $totalInBase += $movement->quantity * $factor;
        }
    }

    // 4. Format total stok ke dalam bentuk hierarki (e.g., 5 Dus, 3 Kotak).
    $displayString = '';
    $remainingStock = $totalInBase;

    // Urutkan unit dari yang terbesar (faktor konversi terbesar) ke terkecil.
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
        'total_in_base' => $totalInBase,
    'display' => $displayString ?: '0 ' . $baseUnit->unit->nama_unit,
    'conversion_factors' => $conversionFactors,
    ];
}

}
