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
}
