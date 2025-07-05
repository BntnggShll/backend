<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ProductStockChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Stok Produk (dalam Satuan Dasar)';

    protected static ?string $description = 'Menampilkan 10 produk dengan stok terbanyak.';
    
    protected static ?int $sort = 3; // Urutan widget di dashboard

    protected function getData(): array
    {
        // 1. Ambil semua produk. Eager load relasi untuk efisiensi.
        $products = Product::with('productUnits.unit')->get();

        $stockData = [];

        // 2. Loop melalui setiap produk dan hitung stoknya menggunakan method yang sudah ada.
        foreach ($products as $product) {
            $stockInfo = $product->calculateStock();
            
            // Kita gunakan total dalam satuan dasar untuk perbandingan yang adil antar produk.
            $totalInBase = $stockInfo['total_in_base'];
            
            if ($totalInBase > 0) {
                $stockData[$product->nama_produk] = $totalInBase;
            }
        }

        // 3. Urutkan produk dari stok terbanyak ke terkecil.
        arsort($stockData);

        // 4. Ambil hanya 10 produk teratas.
        $topProducts = array_slice($stockData, 0, 10, true);

        // 5. Siapkan data untuk format yang dimengerti oleh Chart.js.
        return [
            'datasets' => [
                [
                    'label' => 'Total Stok (Satuan Dasar)',
                    'data' => array_values($topProducts),
                    'backgroundColor' => [
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(153, 102, 255, 0.5)',
                        'rgba(255, 159, 64, 0.5)',
                        'rgba(199, 199, 199, 0.5)',
                        'rgba(83, 102, 255, 0.5)',
                        'rgba(40, 159, 64, 0.5)',
                        'rgba(210, 99, 132, 0.5)',
                    ],
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => array_keys($topProducts),
        ];
    }

    protected function getType(): string
    {
        // Tipe chart: 'bar', 'line', 'pie', 'doughnut', 'radar', 'polarArea'
        return 'bar';
    }
}