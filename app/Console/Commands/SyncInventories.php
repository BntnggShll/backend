<?php
namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Console\Command;

class SyncInventories extends Command
{
    protected $signature = 'app:sync-inventories';
    protected $description = 'Sync the inventories table with data from stock_movements';

    public function handle()
    {
        $this->info('Starting inventory synchronization...');

        // Ambil semua product_unit_id yang unik dari stock_movements
        $productUnitIds = StockMovement::select('product_unit_id')->distinct()->pluck('product_unit_id');

        $progressBar = $this->output->createProgressBar($productUnitIds->count());
        $progressBar->start();

        foreach ($productUnitIds as $id) {
            // Hitung total stok untuk setiap unit
            $quantity = StockMovement::where('product_unit_id', $id)->sum('quantity');

            // Update atau buat record di tabel inventories
            Inventory::updateOrCreate(
                ['product_unit_id' => $id],
                ['quantity' => $quantity]
            );
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info("\nInventory synchronization completed successfully!");
        return 0;
    }
}