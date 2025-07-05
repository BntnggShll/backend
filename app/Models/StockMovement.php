<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Notifications\Notification;

class StockMovement extends Model
{
    use HasFactory;

    public $timestamps = true; 

    protected $fillable = [
        'product_unit_id',
        'quantity',
        'type',
    ];

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }
    protected static function boot()
    {
        parent::boot();

        static::saved(function (StockMovement $stockMovement) {
            if ($stockMovement->type === 'out') {
                self::checkMinStockAndNotify($stockMovement);
            }
        });

        static::deleted(function (StockMovement $stockMovement) {
            if ($stockMovement->type === 'out') {
                self::checkMinStockAndNotify($stockMovement);
            }
        });
    }

    /**
     * Helper method untuk memeriksa stok dan mengirim notifikasi,
     * sekarang dengan logika yang telah disesuaikan.
     */
    private static function checkMinStockAndNotify(StockMovement $stockMovement)
    {
        // Ambil produk induk dari unit yang bergerak
        $product = $stockMovement->productUnit->product;
        if (!$product) {
            return;
        }

        // Cari satuan dasar (terkecil) dari produk tersebut
        $baseUnit = $product->productUnits()->where('is_base_unit', true)->first();
         // Jika produk tidak punya satuan dasar atau tidak ada batas min stok, hentikan.
        if (!$baseUnit || $baseUnit->min_stock_level <= 0) {
            return;
        }
        // Panggil method calculateStock() yang sudah ada di model Product
        // untuk menghitung TOTAL stok produk dalam satuan dasar.
        $stockInfo = $product->calculateStock();
        $currentTotalInBase = $stockInfo['total_in_base'];
        
        $minLevel = $baseUnit->min_stock_level;

        // --- Logika Pencegah Spam Notifikasi ---
        // Kita perlu tahu berapa stok total SEBELUM transaksi ini terjadi.
        // Kita bisa menghitungnya dengan menambahkan kembali kuantitas yang baru saja dijual.
        // Pertama, konversi kuantitas yang dijual ke satuan dasar.
        $movedQuantity = abs($stockMovement->quantity);
        $conversionFactor = $stockInfo['conversion_factors'][$stockMovement->product_unit_id] ?? 0;
        $movedQuantityInBase = $movedQuantity * $conversionFactor;
        
        // Stok lama adalah stok sekarang + yang baru saja terjual.
        $oldTotalInBase = $currentTotalInBase + $movedQuantityInBase;
        // Kirim notifikasi HANYA JIKA stok total jatuh melewati ambang batas.
        // Contoh: min level 10. Stok lama 11, stok baru 9 -> Kirim.
        // Contoh: min level 10. Stok lama 9, stok baru 8 -> JANGAN Kirim lagi.
        
        if ($currentTotalInBase <= $minLevel && $oldTotalInBase > $minLevel) {
            
            // PERUBAHAN 1: Mencari user dengan role NULL
            $recipients = User::where('role' === 'admin')->get();
            if ($recipients->isEmpty()) {
                return; // Tidak ada user yang akan dinotifikasi
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
