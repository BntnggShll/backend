<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{

    use HasFactory;
    protected $table = 'products';
    protected $fillable = ['nama_produk', 'jenis_produk', 'image'];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function calculateStock(): array
    {
        $baseUnit = $this->productUnits()->where('is_base_unit', true)->first();
        if (!$baseUnit) {
            return ['total_in_base' => 0, 'display' => 'Satuan dasar belum di-set', 'conversion_factors' => []];
        }

        // Buat Peta Faktor Konversi (tetap dibutuhkan)
        $conversionFactors = [];
        // ... (logika $calculateFactor tetap sama persis seperti sebelumnya) ...
        $calculateFactor = function ($unit) use (&$calculateFactor, &$conversionFactors) {
            if (isset($conversionFactors[$unit->id]))
                return $conversionFactors[$unit->id];
            if ($unit->is_base_unit)
                return $conversionFactors[$unit->id] = 1;
            $childUnit = $this->productUnits->where('parent_id', $unit->id)->first();
            if (!$childUnit)
                return $conversionFactors[$unit->id] = 0;
            return $conversionFactors[$unit->id] = $childUnit->conversion_rate * $calculateFactor($childUnit);
        };
        foreach ($this->productUnits as $unit) {
            $calculateFactor($unit);
        }

        // --- PERUBAHAN UTAMA: BACA DARI `inventories` ---
        $inventories = Inventory::whereIn('product_unit_id', $this->productUnits->pluck('id'))
            ->get()
            ->keyBy('product_unit_id'); // Jadikan product_unit_id sebagai key array

        $totalInBase = 0;
        foreach ($this->productUnits as $unit) {
            // Ambil kuantitas dari inventory, jika tidak ada anggap 0
            $quantity = $inventories[$unit->id]->quantity ?? 0;
            $factor = $conversionFactors[$unit->id] ?? 0;
            if ($factor > 0) {
                $totalInBase += $quantity * $factor;
            }
        }

        // --- Logika formatting (displayString) tetap sama persis seperti sebelumnya ---
        $displayString = '';
        $remainingStock = $totalInBase;
        // ... (seluruh logika 'foreach ($sortedUnits as $unit)' tetap sama) ...
        $sortedUnits = $this->productUnits->sortByDesc(function ($unit) use ($conversionFactors) {
            return $conversionFactors[$unit->id] ?? 0;
        });
        foreach ($sortedUnits as $unit) {
            $factor = $conversionFactors[$unit->id] ?? 0;
            if ($factor <= 0)
                continue;
            // Dapatkan stok per unit langsung dari tabel inventory untuk ditampilkan
            $quantityForUnit = $inventories[$unit->id]->quantity ?? 0;
            if ($quantityForUnit > 0) {
                // Format ulang display string berdasarkan data inventory per unit
            }
        }
        // Logika display string menjadi lebih kompleks, kita sederhanakan untuk contoh ini
        // Kita tetap pakai cara lama untuk display, tapi sumber totalnya sudah cepat
        foreach ($sortedUnits as $unit) {
            $factor = $conversionFactors[$unit->id] ?? 0;
            if ($factor <= 0)
                continue;
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
