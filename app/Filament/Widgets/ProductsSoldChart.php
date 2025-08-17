<?php
namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductsSoldChart extends ChartWidget
{
    protected static ?string $heading = 'Produk Terjual (Pie Chart)';
    protected static ?int $sort = 2;

    public ?string $selectedMonth = null;
    public ?string $selectedYear = null;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('selectedMonth')
                ->label('Bulan')
                ->options(collect(range(1, 12))->mapWithKeys(fn($m) => [$m => Carbon::create()->month($m)->translatedFormat('F')]))
                ->default(now()->month)
                ->reactive(),

            Forms\Components\Select::make('selectedYear')
                ->label('Tahun')
                ->options($this->getAvailableYears())
                ->default(now()->year)
                ->reactive(),
        ];
    }

    protected function getAvailableYears(): array
    {
        $years = DB::table('orders')
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        return array_combine($years, $years);
    }

    protected function getData(): array
{
    $month = $this->selectedMonth ?? now()->month;
    $year = $this->selectedYear ?? now()->year;

    // Ambil semua order items sesuai filter + hanya status selesai
    $orderItems = DB::table('order_items')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->join('product_units', 'order_items.product_unit_id', '=', 'product_units.id')
        ->join('products', 'product_units.product_id', '=', 'products.id')
        ->select(
            'products.id as product_id',
            'products.nama_produk',
            'order_items.jumlah',
            'order_items.product_unit_id'
        )
        ->whereMonth('orders.created_at', $month)
        ->whereYear('orders.created_at', $year)
        ->where('orders.status', 'selesai') // ✅ hanya order selesai
        ->get();

    // Ambil semua units untuk mapping konversi
    $units = DB::table('product_units')->get()->keyBy('id');

    // Fungsi untuk konversi ke base unit
    $convertToBase = function ($unitId, $jumlah) use ($units) {
        $unit = $units[$unitId];
        $total = $jumlah;
    
        while ($unit && !$unit->is_base_unit) {
            // Kalikan dengan conversion_rate untuk turun ke level bawah
            $child = $units->firstWhere('parent_id', $unit->id);
    
            if (!$child) {
                break; // jaga-jaga kalau ada data nyasar
            }
    
            $total = $total * $child->conversion_rate;
            $unit = $child;
        }
    
        return $total;
    };
    

    // Hitung jumlah per produk dalam satuan dasar
    $totals = [];
    foreach ($orderItems as $item) {
        $jumlahBase = $convertToBase($item->product_unit_id, $item->jumlah);
        if (!isset($totals[$item->nama_produk])) {
            $totals[$item->nama_produk] = 0;
        }
        $totals[$item->nama_produk] += $jumlahBase;
    }

    // Warna chart
    $colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
        '#9966FF', '#FF9F40', '#00A36C', '#C71585',
        '#6495ED', '#DC143C', '#FFD700', '#20B2AA'
    ];

    $labels = array_keys($totals);
    $data = array_values($totals);

    $backgroundColors = collect($labels)->keys()->map(
        fn($i) => $colors[$i % count($colors)]
    );

    return [
        'datasets' => [
            [
                'label' => 'Produk Terjual (Base Unit)',
                'data' => $data,
                'backgroundColor' => $backgroundColors,
                'borderColor' => '#fff',
                'borderWidth' => 2,
            ],
        ],
        'labels' => $labels,
    ];
}


    protected function getType(): string
    {
        return 'pie'; // tampilkan pie chart
    }

    protected static ?string $maxHeight = '275px';
}
