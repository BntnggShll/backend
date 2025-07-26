<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Users;

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
    public function stock_sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }
    protected static function boot()
    {
        parent::boot();

        $validationCallback = function (StockMovement $stockMovement) {
            if ($stockMovement->type === 'out') {
                $inventory = Inventory::where('product_unit_id', $stockMovement->product_unit_id)->first();
                $currentStock = $inventory ? $inventory->quantity : 0;
                $effectiveStock = $currentStock;

                // Jika sedang mengupdate, tambahkan kembali nilai lama untuk perhitungan
                if ($stockMovement->exists) { // $exists bernilai true jika model sudah ada di DB
                    $effectiveStock += abs($stockMovement->getOriginal('quantity'));
                }

                if (abs($stockMovement->quantity) > $effectiveStock) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Jumlah keluar tidak boleh melebihi stok yang tersedia (' . $effectiveStock . ').',
                    ]);
                }
            }
        };

        static::creating($validationCallback);
        static::updating($validationCallback);


        static::saved(function (StockMovement $stockMovement) {
            // Cari record SalesStock yang terhubung melalui ID
            $existingSale = sales_stocks::where('stock_movement_id', $stockMovement->id)->first();

            // KASUS 1: Pergerakan stok adalah 'out' ke seorang sales
            if ($stockMovement->type === 'out' && !is_null($stockMovement->sales_id)) {
                $saleData = [
                    'sales_id' => $stockMovement->sales_id,
                    'product_unit_id' => $stockMovement->product_unit_id,
                    'quantity' => abs($stockMovement->quantity), // Stok sales bertambah
                    'status' => 'in',
                ];

                // Jika record SalesStock sudah ada, update. Jika belum, buat baru.
                // updateOrCreate akan menangani kedua kasus ini dengan cerdas.
                sales_stocks::updateOrCreate(
                    ['stock_movement_id' => $stockMovement->id], // Kunci untuk mencari
                    $saleData  // Data untuk diupdate atau dibuat
                );
            }
            // KASUS 2: Tipe diubah menjadi 'in' atau tidak berhubungan dengan sales
            else {
                // Jika record SalesStock terkait ada (misal: sebelumnya 'out' lalu diubah jadi 'in'), hapus.
                if ($existingSale) {
                    $existingSale->delete();
                }
            }

            // SELALU UPDATE INVENTARIS GUDANG UTAMA
            // Pastikan relasi sudah di-load
            if ($stockMovement->relationLoaded('productUnit')) {
                self::updateInventoryFor($stockMovement->productUnit);
            } else {
                self::updateInventoryFor($stockMovement->load('productUnit')->productUnit);
            }
        });

        static::deleted(function (StockMovement $stockMovement) {
            // Hapus record SalesStock yang terhubung.
            // Sebenarnya sudah ditangani `cascadeOnDelete`, tapi ini sebagai pengaman.
            sales_stocks::where('stock_movement_id', $stockMovement->id)->delete();
    
            // UPDATE INVENTARIS GUDANG UTAMA SETELAH DIHAPUS
            if ($stockMovement->productUnit) {
                self::updateInventoryFor($stockMovement->productUnit);
            }
        });


        // Setiap kali record disimpan (dibuat/diupdate) atau dihapus
        $updateInventoryCallback = function (StockMovement $stockMovement) {
            self::updateInventoryFor($stockMovement->productUnit);
        };

        static::saved($updateInventoryCallback);
        static::deleted($updateInventoryCallback);
    }

    /**
     * Helper method untuk mengupdate tabel inventories untuk satu unit produk.
     */
    public static function updateInventoryFor(ProductUnit $productUnit)
    {
        if (!$productUnit)
            return;

        // Hitung ulang total kuantitas HANYA dari sumber kebenaran: stock_movements
        $newQuantity = StockMovement::where('product_unit_id', $productUnit->id)->sum('quantity');

        // Update atau Buat record di tabel ringkasan 'inventories'
        $inventory = Inventory::updateOrCreate(
            ['product_unit_id' => $productUnit->id],
            ['quantity' => $newQuantity]
        );

        // Setelah inventory diupdate, baru kita cek untuk notifikasi
        self::checkMinStockAndNotify($inventory);
    }

    /**
     * Helper method untuk memeriksa stok dan mengirim notifikasi
     * sekarang menerima object Inventory.
     */
    private static function checkMinStockAndNotify(Inventory $inventory)
    {
        $productUnit = $inventory->productUnit;
        $product = $productUnit->product;

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
        $movedQuantity = abs($inventory->quantity);
        $conversionFactor = $stockInfo['conversion_factors'][$inventory->product_unit_id] ?? 0;
        $movedQuantityInBase = $movedQuantity * $conversionFactor;

        // Stok lama adalah stok sekarang + yang baru saja terjual.
        $oldTotalInBase = $currentTotalInBase + $movedQuantityInBase;
        // Kirim notifikasi HANYA JIKA stok total jatuh melewati ambang batas.
        // Contoh: min level 10. Stok lama 11, stok baru 9 -> Kirim.
        // Contoh: min level 10. Stok lama 9, stok baru 8 -> JANGAN Kirim lagi.

        if ($currentTotalInBase <= $minLevel && $oldTotalInBase > $minLevel) {

            // PERUBAHAN 1: Mencari user dengan role NULL
            $recipients = User::where('role', 'admin')->get();
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
