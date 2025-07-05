<?php

namespace App\Filament\Resources\StockMovementChartResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\OrderItem; 
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockmovementChart extends ChartWidget
{
    protected static ?string $heading = 'Analisis Penjualan per Tipe User (30 Hari Terakhir)';
    protected static ?int $sort = 2;
    public ?string $filter = 'by_role';

    protected function getFilters(): ?array
    {
        return [
            'by_role' => 'Berdasarkan Role',
            'customer' => 'Top 5 Customer',
            'reseller' => 'Top 5 Reseller',
            'sales' => 'Top 5 Sales',
        ];
    }

    protected function getData(): array
    {
        // Query dimulai dari OrderItem, lalu di-JOIN ke atas
        $query = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id') // Gabung ke tabel orders
            ->join('users', 'orders.user_id', '=', 'users.id')      // Gabung ke tabel users
            ->where('orders.created_at', '>=', now()->subDays(30)); // Filter 30 hari terakhir

        // Logika untuk mengubah query berdasarkan filter yang aktif
        if ($this->filter === 'by_role') {
            $data = $query
                ->select('users.role', DB::raw('SUM(order_items.jumlah) as total_quantity'))
                ->groupBy('users.role')
                ->pluck('total_quantity', 'role');
        } else {
            // Filter untuk top 5 customer/reseller/sales
            $data = $query
                ->select('users.name', DB::raw('SUM(order_items.jumlah) as total_quantity'))
                ->where('users.role', $this->filter)
                ->groupBy('users.name')
                ->orderBy('total_quantity', 'desc')
                ->limit(5)
                ->pluck('total_quantity', 'name');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Unit Terjual',
                    'data' => $data->values(),
                    'backgroundColor' => [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                    ],
                ],
            ],
            'labels' => $data->keys(),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; 
    }
}
