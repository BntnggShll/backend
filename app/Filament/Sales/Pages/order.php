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
    protected static ?int $navigationSort = -2; // Taruh di paling atas

    // Properti untuk tampilan halaman
    protected static string $view = 'filament.sales.pages.order';
    protected static ?string $title = 'Buat Pesanan';
    protected ?string $subheading = 'Pilih produk dari stok yang Anda pegang untuk membuat pesanan baru.';

    // Properti untuk menampung data (State)
    public array $orderQuantities = [];
    public array $salesStocks = [];
    public array $productsByUnit = [];
    // Di dalam class Order
public ?string $paymentMethod = 'cash';

    /**
     * Method ini berjalan saat halaman pertama kali dimuat.
     * Tugasnya adalah mengambil semua data yang diperlukan.
     */
    public function mount(): void
    {
        $salesId = auth()->id();

        // 1. Ambil stok yang dipegang oleh sales yang sedang login.
        $salesStockRecords = sales_stocks::where('sales_id', $salesId)->get();
        
        $stockTotals = [];
        foreach ($salesStockRecords->groupBy('product_unit_id') as $productUnitId => $records) {
            $in = $records->where('status', 'in')->sum('quantity');
            $out = $records->where('status', 'out')->sum('quantity');
            $stockTotals[$productUnitId] = $in - $out;
        }
        $this->salesStocks = $stockTotals;

        // 2. Ambil semua produk yang tersedia, dan kelompokkan berdasarkan nama unitnya.
        $this->productsByUnit = ProductUnit::with(['product', 'unit'])
            ->whereIn('id', array_keys($this->salesStocks))
            ->get()
            ->groupBy('unit.nama_unit')
            ->toArray();

        // 3. Inisialisasi kuantitas pesanan menjadi 0 untuk semua produk.
        foreach ($this->salesStocks as $productUnitId => $quantity) {
            $this->orderQuantities[$productUnitId] = 0;
        }
    }

    /**
     * Aksi untuk menambah kuantitas pesanan.
     */
    public function incrementQuantity(int $productUnitId): void
    {
        $maxStock = $this->salesStocks[$productUnitId] ?? 0;
        if ($this->orderQuantities[$productUnitId] < $maxStock) {
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
     */
    public function decrementQuantity(int $productUnitId): void
    {
        if ($this->orderQuantities[$productUnitId] > 0) {
            $this->orderQuantities[$productUnitId]--;
        }
    }

    /**
     * Method ini adalah action utama untuk membuat pesanan.
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
                    $totalPrice += $productUnit->harga_jual * $quantity;
                }

                $order = OrderModel::create([
                    'user_id' => $salesId,
                    'total_harga' => $totalPrice,
                    'shipping_cost' => 0,
                    'created_at' => now(),
                ]);

                Payment::create([
                    'order_id' => $order->id,
                    'total_pembayaran' => $order->total_harga + $order->shipping_cost, // Total harga + ongkir
                    'metode_pembayaran' => $this->paymentMethod, // Dari pilihan di form
                    'status_pembayaran' => 'menunggu', // Status awal
                ]);
                foreach ($cartItems as $productUnitId => $quantity) {
                    $productUnit = ProductUnit::find($productUnitId);
                    
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_unit_id' => $productUnitId,
                        'jumlah' => $quantity,
                        'harga' => $productUnit->harga_jual,
                    ]);

                    // PERBAIKAN: Buat catatan di 'sales_stocks' dengan menyertakan product_unit_id
                    sales_stocks::create([
                        'sales_id' => $salesId,
                        'product_unit_id' => $productUnitId, // <-- INI YANG DITAMBAHKAN
                        'quantity' => $quantity,
                        'status' => 'out',
                    ]);
                    
                }

                $this->reset('orderQuantities');
                $this->mount();

                Notification::make()->title('Pesanan berhasil dibuat!')->success()->send();
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
