<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Request;

class OrderController
{
    public function create(Request $request)
    {

        $client_id = $request->get("client");

        $existingOrder = Order::where('client', $client_id)->where('status', 'pending')->first();

        if ($existingOrder) {
            return response()->json([
                'error' => ['message' => 'Ya existe un pedido pendiente para este cliente'],
            ], 400);
        }

        $data = $request->validate([
            'client' => 'required|string',
            'comment' => 'nullable|string',
            'status' => 'required|string',
            'total_amount' => 'nullable|integer',
            'date' => 'required|integer',
            'id' => 'required|string',
            'client_name' => 'required|string'
        ]);

        $order = Order::create($data);

        return response()->json([$order], 201);
    }

    public function pending($id = null)
    {

        if ($id) {
            $orders = Order::where('status', 'pending')
                ->where('client', $id)
                ->orderBy('date', 'asc')
                ->get();
        } else {
            $orders = Order::where('status', 'pending')
                ->orderBy('date', 'asc')
                ->get();
        }

        return response()->json($orders);
    }

    public function completed(Request $request, $id = null)
    {

        $currentMonthStart = $request->input('currentMonthStart');
        $currentMonthEnd = $request->input('currentMonthEnd');

        if ($id) {
            $orders = Order::where('status', 'completed')
                ->where('client', $id)
                ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
                ->orderBy('date', 'desc')
                ->get();
        } else {
            $orders = Order::where('status', 'completed')
                ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
                ->orderBy('date', 'desc')
                ->get();
        }

        return response()->json($orders);
    }

    public function products($id    )
    {
        $order = Order::with('products')->find($id);

        if (!$order) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

        return response()->json($order);
    }

    public function update(Request $request)
    {

        $orderId = $request->input('order');

    }
}
