<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;
    protected $table = 'shipments';
    protected $fillable = ['order_id', 'sales_id','perkiraan_pengiriman', 'status_pengiriman'];

    public function order() {
        return $this->belongsTo(Order::class);
    }
    public function user() {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Mendengarkan event 'saved' (setelah record dibuat atau di-update)
        static::saved(function (StockMovement $stockMovement) {
            // Kita hanya peduli pada pergerakan stok keluar (penjualan)
            if ($stockMovement->type === 'out') {
                self::checkMinStockAndNotify($stockMovement);
            }
        });

        // Mendengarkan event 'deleted' (setelah record dihapus, misal: penjualan dibatalkan)
        static::deleted(function (StockMovement $stockMovement) {
            // Stok akan bertambah kembali, jadi kita cek lagi
            if ($stockMovement->type === 'out') {
                self::checkMinStockAndNotify($stockMovement);
            }
        });
    }

    /**
     * Helper method untuk memeriksa stok dan mengirim notifikasi.
     * Dibuat static agar bisa dipanggil dari method boot().
     */
    private static function checkMinStockAndNotify(StockMovement $stockMovement)
    {
        $productUnit = $stockMovement->productUnit;

        // Jika tidak ada batas stok minimum yang di-set, hentikan proses.
        if (!$productUnit || $productUnit->min_stock_level <= 0) {
            return;
        }

        // Hitung kuantitas stok saat ini dengan menjumlahkan semua pergerakan.
        // Ini adalah cara paling akurat untuk mendapatkan stok terkini.
        $currentQuantity = self::where('product_unit_id', $productUnit->id)->sum('quantity');

        // Kirim notifikasi HANYA JIKA stok saat ini jatuh di bawah atau sama dengan batas minimum.
        if ($currentQuantity <= $productUnit->min_stock_level) {
            
            // Logika untuk mencegah spam notifikasi bisa ditambahkan di sini.
            // Untuk saat ini, kita akan kirim setiap kali kondisi terpenuhi.

            // Ambil semua user admin untuk dikirimi notifikasi.
            $recipients = User::where('role', 'admin')->get();

            if ($recipients->isEmpty()) {
                return; // Tidak ada admin untuk dinotifikasi
            }

            $productName = $productUnit->product->nama_produk;
            $unitName = $productUnit->unit->nama_unit;

            Notification::make()
                ->title('Peringatan Stok Minimum')
                ->body("Stok untuk {$productName} satuan {$unitName} telah mencapai batas minimum. Sisa stok: {$currentQuantity}.")
                ->warning()
                ->sendToDatabase($recipients);
        }
    }

}
