<?php

namespace App\Filament\Sales\Pages;

use App\Models\Inventory;
use App\Models\Order as OrderModel;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductUnit;
use App\Models\sales_stocks;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Order extends Page implements HasForms
{
    use InteractsWithForms;

    // Properti untuk navigasi
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Buat Pesanan Baru';
    protected static ?string $navigationGroup = 'Manajemen Pesanan';
    protected static ?int $navigationSort = -2;

    // Properti untuk tampilan halaman
    protected static string $view = 'filament.sales.pages.order';
    protected static ?string $title = 'Buat Pesanan';
    protected ?string $subheading = 'Pilih produk dari stok yang Anda pegang untuk membuat pesanan baru.';

    // Properti untuk menampung data (State)
    public array $orderQuantities = [];
    public array $salesStocks = [];
    public array $productsByProduct = [];
    public ?string $paymentMethod = 'Cash';

    /**
     * Method ini berjalan saat halaman pertama kali dimuat.
     */
    public function mount(): void
    {
        $this->orderQuantities = [];
        $this->recalculateAndPrepareViewData();
    }

    /**
     * Method pusat untuk perhitungan stok dan persiapan data.
     */
    public function recalculateAndPrepareViewData(): void
    {
        $salesId = auth()->id();

        // LANGKAH 1 & 2 (SAMA SEPERTI SEBELUMNYA)
        $inventoryRecords = Inventory::all();
        $physicalStocks = [];
        foreach ($inventoryRecords->groupBy('product_unit_id') as $productUnitId => $records) {
            $qty = $records->sum('quantity_sales');
            if ($qty > 0) {
                $physicalStocks[$productUnitId] = $qty;
            }
        }

        $effectiveStocks = $physicalStocks;
        $allPhysicalUnits = ProductUnit::with('parent')->whereIn('id', array_keys($physicalStocks))->get();

        foreach ($allPhysicalUnits as $childUnit) {
            if ($childUnit->parent && $childUnit->conversion_rate > 0) {
                $childQuantity = $physicalStocks[$childUnit->id] ?? 0;
        
                // Konversi stok anak ke parent
                $derivedParentQuantity = intdiv($childQuantity, $childUnit->conversion_rate);
        
                $effectiveStocks[$childUnit->parent->id] = ($effectiveStocks[$childUnit->parent->id] ?? 0) + $derivedParentQuantity;
            }
        }

        // LANGKAH 3: PENYESUAIAN BERDASARKAN KERANJANG (SAMA SEPERTI SEBELUMNYA)
        $cartItems = $this->getCartItems();
        if (!empty($cartItems)) {
            $allUnitsInCart = ProductUnit::with(['children', 'parent'])->whereIn('id', array_keys($cartItems))->get();
            foreach ($allUnitsInCart as $unitInCart) {
                $quantityInCart = $cartItems[$unitInCart->id];

                if (isset($effectiveStocks[$unitInCart->id])) {
                    $effectiveStocks[$unitInCart->id] -= $quantityInCart;
                }

                if ($unitInCart->children->isNotEmpty()) {
                    foreach ($unitInCart->children as $childUnit) {
                        if (isset($effectiveStocks[$childUnit->id]) && $childUnit->conversion_rate > 0) {
                            $reductionAmount = $quantityInCart * $childUnit->conversion_rate;
                            $effectiveStocks[$childUnit->id] -= $reductionAmount;
                        }
                    }
                }

                if ($unitInCart->parent) {
                    $parentUnit = $unitInCart->parent;
                    if (isset($effectiveStocks[$parentUnit->id]) && $unitInCart->conversion_rate > 0) {
                        $reductionAmountInParentUnit = $quantityInCart / $unitInCart->conversion_rate;
                        $effectiveStocks[$parentUnit->id] -= $reductionAmountInParentUnit;
                    }
                }
            }
        }

        // --- LANGKAH 4 (BARU): Bulatkan ke bawah stok parent yang mungkin menjadi desimal ---
        $allUnitDetails = ProductUnit::whereIn('id', array_keys($effectiveStocks))->get()->keyBy('id');
        foreach ($effectiveStocks as $unitId => $stock) {
            // Cek apakah unit ini adalah parent (punya children)
            if (isset($allUnitDetails[$unitId]) && $allUnitDetails[$unitId]->children()->exists()) {
                // Jika ya, bulatkan stoknya ke bawah ke integer terdekat
                $effectiveStocks[$unitId] = floor($stock);
            }
        }

        // --- LANGKAH 5: Simpan hasil akhir & siapkan data view ---
        $this->salesStocks = $effectiveStocks;
        $allUnitIds = array_keys($this->salesStocks);
        $productUnitsForView = ProductUnit::with(['product', 'unit'])
            ->whereIn('id', $allUnitIds)
            ->get();

        foreach ($allUnitIds as $id) {
            if (!isset($this->orderQuantities[$id])) {
                $this->orderQuantities[$id] = 0;
            }
        }

        $this->productsByProduct = $productUnitsForView->groupBy('product.nama_produk')->toArray();
    }

    /**
     * Aksi untuk menambah kuantitas pesanan.
     * Tidak perlu diubah karena $this->salesStocks sudah dibulatkan.
     */
    public function incrementQuantity(int $productUnitId): void
    {
        $maxStock = $this->salesStocks[$productUnitId] ?? 0;

        // Perbandingan ini sekarang aman karena $maxStock untuk parent sudah di-floor()
        if ($maxStock > 0 && ($this->orderQuantities[$productUnitId] ?? 0) < $maxStock) {
            $this->orderQuantities[$productUnitId]++;
            $this->recalculateAndPrepareViewData();
        } else {
            Notification::make()
                ->title('Stok Tidak Cukup')
                ->body('Stok untuk item ini sudah habis atau terpakai oleh item lain di keranjang.')
                ->warning()
                ->send();
        }
    }

    /**
     * Aksi untuk mengurangi kuantitas pesanan.
     */
    public function decrementQuantity(int $productUnitId): void
    {
        if (($this->orderQuantities[$productUnitId] ?? 0) > 0) {
            $this->orderQuantities[$productUnitId]--;
            $this->recalculateAndPrepareViewData();
        }
    }

    // --- SISA METHOD (getActions, createOrder, getCartItems) TIDAK ADA PERUBAHAN ---
    // ... (salin sisa method dari file sebelumnya)
    // ...

    protected function getActions(): array
    {
        return [
            Action::make('createOrder')
                ->label('Buat Pesanan')
                ->icon('heroicon-o-check-circle')
                ->action('createOrder')
                ->disabled(count($this->getCartItems()) === 0),
        ];
    }

    public function createOrder(): void
    {
        $cartItems = $this->getCartItems();
        if (empty($cartItems)) {
            Notification::make()->title('Keranjang Kosong')->warning()->send();
            return;
        }

        try {
            DB::transaction(function () use ($cartItems) {
                $salesId = auth()->id();
                $totalPrice = 0;

                foreach ($cartItems as $productUnitId => $quantity) {
                    $productUnit = ProductUnit::find($productUnitId);
                    if ($productUnit) {
                        $totalPrice += $productUnit->harga_jual * $quantity;
                    }
                }

                $order = OrderModel::create([
                    'user_id' => $salesId,
                    'total_harga' => $totalPrice,
                    'status' => 'selesai',
                ]);

                Payment::create([
                    'order_id' => $order->id,
                    'total_pembayaran' => $order->total_harga,
                    'metode_pembayaran' => $this->paymentMethod,
                    'status_pembayaran' => 'menunggu',
                ]);

                foreach ($cartItems as $productUnitId => $quantity) {
                    $productUnit = ProductUnit::find($productUnitId);
                    if (!$productUnit)
                        continue;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_unit_id' => $productUnitId,
                        'jumlah' => $quantity,
                        'harga' => $productUnit->harga_jual,
                    ]);

                    sales_stocks::create([
                        'sales_id' => $salesId,
                        'product_unit_id' => $productUnitId,
                        'quantity' => $quantity,
                        'status' => 'out',
                        'order_id' => $order->id,
                    ]);
                }

                Notification::make()->title('Pesanan berhasil dibuat!')->success()->send();
                $this->reset('paymentMethod');
                $this->mount();
            });
        } catch (\Exception $e) {
            Notification::make()->title('Terjadi Kesalahan')->body($e->getMessage())->danger()->send();
        }
    }

    public function getCartItems(): array
    {
        return array_filter($this->orderQuantities, fn($quantity) => $quantity > 0);
    }
}