<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrdersChart extends ChartWidget
{
    protected static ?string $heading = 'Order per Hari';
    protected static ?int $sort = 1;

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

        $data = DB::table('orders')
            ->selectRaw('DAY(created_at) as day, COUNT(*) as total')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $daysInMonth = Carbon::createFromDate($year, $month)->daysInMonth;

        $labels = range(1, $daysInMonth);
        $values = array_fill(1, $daysInMonth, 0);

        foreach ($data as $row) {
            $values[$row->day] = $row->total;
        }

        return [
            'datasets' => [
                [
                    'label' => "Order Bulan " . Carbon::create()->month($month)->translatedFormat('F') . " $year",
                    'data' => array_values($values),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // bisa diganti 'bar', 'pie', dll
    }

    protected static ?string $maxHeight = '300px';
}
