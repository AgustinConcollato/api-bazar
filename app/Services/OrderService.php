<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderProducts;

class OrderService
{
    public function create($validated)
    {
        $clientId = $validated['client_id'];

        $existingOrder = Order::where('client_id', $clientId)->where('status', 'pending')->first();

        if ($existingOrder) {
            throw new \Exception('Ya existe un pedido pendiente para este cliente', 400);
        }


        $validated['comment'] = isset($validated['comment']) ? strip_tags($validated['comment']) : null;
        $validated['total_amount'] ??= 0;

        $order = Order::create($validated);
        return $order;
    }

    public function add($validated)
    {

        $price = $validated['price'];
        $quantity = $validated['quantity'];
        $discount = $validated['discount'];

        $subtotal = $discount ?
            ($price - ($price * $discount) / 100) * $quantity :
            $price * $quantity;

        $validated['subtotal'] = $subtotal;

        $productInOrder = OrderProducts::where('order_id', $validated['order_id'])
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($productInOrder) {
            throw new \Exception("El producto ya esta en la lista", 400);
        }

        $product = OrderProducts::create($validated);

        if ($product) {
            $order = Order::find($validated['order_id']);
            $order->update(['total_amount' => $order->total_amount + $subtotal]);

            return $product;
        }
    }
}