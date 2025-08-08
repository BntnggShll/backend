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
                ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => Carbon::create()->month($m)->translatedFormat('F')]))
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

        $data = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_units', 'order_items.product_unit_id', '=', 'product_units.id')
            ->join('products', 'product_units.product_id', '=', 'products.id')
            ->selectRaw('products.nama_produk, SUM(order_items.jumlah) as total')
            ->whereMonth('orders.created_at', $month)
            ->whereYear('orders.created_at', $year)
            ->groupBy('products.nama_produk')
            ->orderByDesc('total')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Produk Terjual',
                    'data' => $data->pluck('total'),
                ],
            ],
            'labels' => $data->pluck('nama_produk'),
        ];
    }

    protected function getType(): string
    {
        return 'pie'; // tampilkan pie chart
    }

    protected static ?string $maxHeight = '275px';
}
