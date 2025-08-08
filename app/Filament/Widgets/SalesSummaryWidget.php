<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\Widget;

class SalesSummaryWidget extends Widget
{
    protected static string $view = 'filament.widgets.sales-summary-widget';

    protected int | string | array $columnSpan = 'full';

    public function getSalesData(): array
    {
        return [
            'sales' => Order::whereHas('user', fn ($q) => $q->where('role', 'sales'))->sum('total_harga'),
            'reseller' => Order::whereHas('user', fn ($q) => $q->where('role', 'reseller'))->sum('total_harga'),
            'customer' => Order::whereHas('user', fn ($q) => $q->where('role', 'customer'))->sum('total_harga'),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view(static::$view, [
            'data' => $this->getSalesData(),
        ]);
    }
    public static function getWidgets(): array
{
    return [
        \App\Filament\Widgets\SalesSummaryWidget::class,
    ];
}

}

