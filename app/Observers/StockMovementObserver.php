<?php

namespace App\Observers;

use App\Models\Inventory;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use App\Models\User; // Import User model
use Filament\Notifications\Notification; // Import Notification

class StockMovementObserver
{
    private function updateInventory(ProductUnit $productUnit)
    {
        // Ambil kuantitas lama sebelum dihitung ulang
        $oldQuantity = Inventory::where('product_unit_id', $productUnit->id)->first()->quantity ?? 0;

        // Hitung ulang total kuantitas dari log
        $newQuantity = StockMovement::where('product_unit_id', $productUnit->id)->sum('quantity');

        // Simpan ke tabel inventories
        Inventory::updateOrCreate(
            ['product_unit_id' => $productUnit->id],
            ['quantity' => $newQuantity]
        );

        // --- LOGIKA NOTIFIKASI DIMULAI DI SINI ---
        $minLevel = $productUnit->min_stock_level;

        // Kirim notif hanya jika ada batas minimum yang di-set,
        // dan stok baru di bawah batas, DAN stok lama masih di atas batas (untuk menghindari spam notif)
        if ($minLevel > 0 && $newQuantity <= $minLevel && $oldQuantity > $minLevel) {
            // Ambil semua user yang ingin Anda notifikasi (contoh: semua admin)
            $recipients = User::where('is_admin', true)->get(); // Asumsi Anda punya kolom 'is_admin'
            $productName = $productUnit->product->nama_produk;
            $unitName = $productUnit->unit->nama_unit;

            Notification::make()
                ->title('Stok Produk Minimum')
                ->body("Stok untuk {$productName} satuan {$unitName} telah mencapai batas minimum. Sisa stok: {$newQuantity}.")
                ->warning() // Tipe notifikasi: warning, success, danger
                ->sendToDatabase($recipients); // Kirim notifikasi ke user tersebut
        }
    }
    public function saved(StockMovement $stockMovement): void
    {
        $this->updateInventory($stockMovement->productUnit);
    }

    public function deleted(StockMovement $stockMovement): void
    {
        $this->updateInventory($stockMovement->productUnit);
    }
}