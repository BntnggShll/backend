<?php

namespace App\Filament\Sales\Pages;

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
    protected static ?int $navigationSort = -2;

    // Properti untuk tampilan halaman
    protected static string $view = 'filament.sales.pages.order';
    protected static ?string $title = 'Buat Pesanan';
    protected ?string $subheading = 'Pilih produk dari stok yang Anda pegang untuk membuat pesanan baru.';

    // Properti untuk menampung data (State)
    public array $orderQuantities = [];
    public array $salesStocks = [];
    public array $productsByProduct = [];
    public ?string $paymentMethod = 'cash';

    /**
     * Method ini berjalan saat halaman pertama kali dimuat.
     * Logika di sini telah dirombak total untuk mendukung stok virtual.
     */
    public function mount(): void
    {
        $salesId = auth()->id();

        // --- LANGKAH 1: Hitung stok FISIK yang dipegang sales ---
        $salesStockRecords = sales_stocks::where('sales_id', $salesId)->get();
        $physicalStocks = [];
        foreach ($salesStockRecords->groupBy('product_unit_id') as $productUnitId => $records) {
            $in = $records->where('status', 'in')->sum('quantity');
            $out = $records->where('status', 'out')->sum('quantity');
            $currentStock = $in - $out;
            if ($currentStock > 0) {
                $physicalStocks[$productUnitId] = $currentStock;
            }
        }

        // --- LANGKAH 2: Hitung stok EFEKTIF (Fisik + Virtual dari Parent) ---
        $effectiveStocks = $physicalStocks;

        // Ambil model ProductUnit untuk stok fisik, beserta relasi children-nya
        $parentUnits = ProductUnit::with('children')
            ->whereIn('id', array_keys($physicalStocks))
            ->get();

        foreach ($parentUnits as $parentUnit) {
            // Jika unit ini punya turunan (misal: Kotak punya Saset)
            if ($parentUnit->children->isNotEmpty()) {
                $parentQuantity = $physicalStocks[$parentUnit->id];

                foreach ($parentUnit->children as $childUnit) {
                    // Hitung berapa banyak unit anak yang bisa didapat dari parent
                    if ($childUnit->conversion_rate > 0) {
                        $derivedChildQuantity = $parentQuantity * $childUnit->conversion_rate;

                        // Tambahkan stok virtual ke stok efektif.
                        // Jika sudah ada stok fisik untuk anak, jumlahkan.
                        if (isset($effectiveStocks[$childUnit->id])) {
                            $effectiveStocks[$childUnit->id] += $derivedChildQuantity;
                        } else {
                            $effectiveStocks[$childUnit->id] = $derivedChildQuantity;
                        }
                    }
                }
            }
        }
        
        // Simpan hasil perhitungan stok efektif sebagai sumber kebenaran
        $this->salesStocks = $effectiveStocks;

        // --- LANGKAH 3: Siapkan data untuk ditampilkan di View ---
        $allUnitIds = array_keys($this->salesStocks);
        
        $productUnitsForView = ProductUnit::with(['product', 'unit'])
            ->whereIn('id', $allUnitIds)
            ->get();

        $this->productsByProduct = $productUnitsForView->groupBy('product.nama_produk')->toArray();

        // --- LANGKAH 4: Inisialisasi kuantitas pesanan menjadi 0 ---
        $this->orderQuantities = [];
        foreach ($allUnitIds as $id) {
            $this->orderQuantities[$id] = 0;
        }
    }

    /**
     * Aksi untuk menambah kuantitas pesanan.
     * Logika ini tidak perlu diubah karena sudah memeriksa $this->salesStocks.
     */
    public function incrementQuantity(int $productUnitId): void
    {
        $maxStock = $this->salesStocks[$productUnitId] ?? 0;
        if (($this->orderQuantities[$productUnitId] ?? 0) < $maxStock) {
            $this->orderQuantities[$productUnitId]++;
        } else {
            Notification::make()
                ->title('Stok Tidak Cukup')
                ->body('Anda tidak bisa memesan melebihi stok yang Anda pegang.')
                ->warning()
                ->send();
        }
    }

    /**
     * Aksi untuk mengurangi kuantitas pesanan.
     * Tidak perlu diubah.
     */
    public function decrementQuantity(int $productUnitId): void
    {
        if (($this->orderQuantities[$productUnitId] ?? 0) > 0) {
            $this->orderQuantities[$productUnitId]--;
        }
    }

    /**
     * Method ini adalah action utama untuk membuat pesanan.
     * Tidak perlu diubah.
     */
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

    /**
     * Logika utama untuk menyimpan pesanan ke database.
     * Tidak perlu diubah.
     */
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
                    'shipping_cost' => 0,
                    'status' => 'completed',
                ]);

                Payment::create([
                    'order_id' => $order->id,
                    'total_pembayaran' => $order->total_harga + $order->shipping_cost,
                    'metode_pembayaran' => $this->paymentMethod,
                    'status_pembayaran' => 'paid',
                ]);

                foreach ($cartItems as $productUnitId => $quantity) {
                    $productUnit = ProductUnit::find($productUnitId);
                    if (!$productUnit) continue;

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
                
                $this->reset('orderQuantities', 'paymentMethod');
                $this->mount();
            });
        } catch (\Exception $e) {
            Notification::make()->title('Terjadi Kesalahan')->body($e->getMessage())->danger()->send();
        }
    }

    /**
     * Helper untuk mendapatkan item yang ada di keranjang (quantity > 0).
     */
    public function getCartItems(): array
    {
        return array_filter($this->orderQuantities, fn ($quantity) => $quantity > 0);
    }
}
