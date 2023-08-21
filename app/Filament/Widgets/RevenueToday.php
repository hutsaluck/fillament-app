<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueToday extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalRevenue = Order::whereDate('created_at', date('Y-m-d'))->sum('price') / 100;

        return [
            Stat::make('Revenue Today (USD)',
                number_format($totalRevenue, 2))
        ];
    }
}
