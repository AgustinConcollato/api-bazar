<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Symfony\Component\HttpFoundation\Request;

class OrderController
{
    public function create(Request $request)
    {

        $client_id = $request->get("client");

        $existingOrder = Order::where('client', $client_id)->where('status', 'pending')->first();

        if ($existingOrder) {
            return response()->json([
                'error' => ['message' => 'Ya existe un pedido pendiente para este cliente.'],
            ], 400);
        }

        $data = $request->validate([
            'client' => 'required|string',
            'comment' => 'nullable|string',
            'status' => 'required|string',
            'total_amount' => 'nullable|integer',
            'date' => 'required|integer',
            'id' => 'required|string'
        ]);

        $order = Order::create($data);

        return response()->json([$order], 201);
    }
}
