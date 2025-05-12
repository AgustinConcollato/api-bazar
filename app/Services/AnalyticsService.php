<?php

namespace App\Services;

use App\Models\Order;

final class AnalyticsService
{
    public function netProfit($validated)
    {
        $orders = Order::where('status', 'completed')
            ->whereYear('created_at', $validated['year'])
            ->when(isset($validated['month']), function ($query) use ($validated) {
                return $query->whereMonth('created_at', $validated['month']);
            })
            ->get();

        $orders->each(function ($order) {
            $order->total_cost = $order->products->sum(function ($product) {
                return $product->quantity * $product->purchase_price;
            });
        });

        $netProfit = $orders->sum('total_amount') - $orders->sum('total_cost');
        return $netProfit;
    }

    public function grossProfit($validated)
    {
        $orders = Order::where('status', 'completed')
            ->whereYear('created_at', $validated['year'])
            ->when(isset($validated['month']), function ($query) use ($validated) {
                return $query->whereMonth('created_at', $validated['month']);
            })
            ->get();

        $grossProfit = $orders->sum('total_amount');
        return $grossProfit;
    }

    public function profitPercentage($validated)
    {
        $grossProfit = $this->grossProfit($validated);
        $netProfit = $this->netProfit($validated);
        
        if ($grossProfit == 0) {
            return 0;
        }
        
        return $netProfit / $grossProfit * 100;
    }

    public function cost($validated)
    {
        $orders = Order::where('status', 'completed')
            ->whereYear('created_at', $validated['year'])
            ->when(isset($validated['month']), function ($query) use ($validated) {
                return $query->whereMonth('created_at', $validated['month']);
            })
            ->get();

        $cost = $orders->sum(function ($order) {
            return $order->products->sum(function ($product) {
                return $product->quantity * $product->purchase_price;
            });
        });
        return $cost;
    }

    public function resume($validated)
    {
        return [
            'gross_profit' => $this->grossProfit($validated),
            'net_profit' => $this->netProfit($validated),
            'profit_percentage' => $this->profitPercentage($validated),
            'cost' => $this->cost($validated)
        ];
    }

    public function compareWithPreviousMonth($validated)
    {
        // Obtener datos del mes actual
        $currentMonthData = $this->resume($validated);

        // Calcular el mes anterior
        $previousMonth = $validated['month'] - 1;
        $previousYear = $validated['year'];

        if ($previousMonth < 1) {
            $previousMonth = 12;
            $previousYear--;
        }

        $orders = Order::where('status', 'completed')
            ->whereYear('created_at', $previousYear)
            ->when(isset($validated['month']), function ($query) use ($validated) {
                return $query->whereMonth('created_at', $validated['month']);
            })
            ->get();

        // Obtener datos del mes anterior
        $previousMonthData = $this->resume([
            'year' => $previousYear,
            'month' => $previousMonth,
        ]);

        // Calcular las diferencias porcentuales
        return [
            'current_month' => $currentMonthData,
            'previous_month' => $previousMonthData,
            'orders' => [
                'quantity' => $orders->count(),
                'total_amount' => $orders->sum('total_amount'),
            ],
            'comparison' => [
                'gross_profit_change' => $this->calculatePercentageChange(
                    $previousMonthData['gross_profit'],
                    $currentMonthData['gross_profit']
                ),
                'net_profit_change' => $this->calculatePercentageChange(
                    $previousMonthData['net_profit'],
                    $currentMonthData['net_profit']
                ),
                'profit_percentage_change' => $this->calculatePercentageChange(
                    $previousMonthData['profit_percentage'],
                    $currentMonthData['profit_percentage']
                ),
                'cost_change' => $this->calculatePercentageChange(
                    $previousMonthData['cost'],
                    $currentMonthData['cost']
                )
            ],
        ];
    }

    private function calculatePercentageChange($previous, $current)
    {
        if ($previous == 0 && $current == 0) {
            return 0;
        }
        
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return (($current - $previous) / abs($previous)) * 100;
    }

    public function compareWithPreviousYear($validated)
    {
        // Obtener datos del año actual
        $currentYearData = $this->resume($validated);

        // Calcular el año anterior
        $previousYear = $validated['year'] - 1;

        // Obtener datos del año anterior
        $previousYearData = $this->resume([
            'year' => $previousYear,
            'month' => $validated['month'] ?? null
        ]);

        // Calcular las diferencias porcentuales
        return [
            'current_year' => $currentYearData,
            'previous_year' => $previousYearData,
            'comparison' => [
                'gross_profit_change' => $this->calculatePercentageChange(
                    $previousYearData['gross_profit'],
                    $currentYearData['gross_profit']
                ),
                'net_profit_change' => $this->calculatePercentageChange(
                    $previousYearData['net_profit'],
                    $currentYearData['net_profit']
                ),
                'profit_percentage_change' => $this->calculatePercentageChange(
                    $previousYearData['profit_percentage'],
                    $currentYearData['profit_percentage']
                ),
                'cost_change' => $this->calculatePercentageChange(
                    $previousYearData['cost'],
                    $currentYearData['cost']
                )
            ]
        ];
    }
}
