<?php

namespace App\Services;

use App\Models\Client;
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

        $client = Client::with('address')->find($clientId);

        $selectedAddress = null;

        if ($client && $client->address->count()) {
            $selected = $client->address->firstWhere('status', 'selected');
            $selectedAddress = $selected ? $selected->toArray() : null;
        }

        $validated['comment'] = isset($validated['comment']) ? strip_tags($validated['comment']) : null;
        $validated['total_amount'] ??= 0;
        $validated['address'] = $selectedAddress ? json_encode($selectedAddress) : null;

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

    public function get($validated)
    {
        $query = Order::query()->with('payments');

        // Si se especificó un estado (status), aplicarlo
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Si se especificó un año, aplicarlo
        if (!empty($validated['year'])) {
            $query->whereYear('updated_at', $validated['year']);
        }

        // Si se especificó un mes, aplicarlo
        if (!empty($validated['month'])) {
            $query->whereMonth('updated_at', $validated['month']);
        }

        // Si se especificó un client_id, usar ese (para admin o dashboard),
        // sino usar el ID del cliente autenticado (para cliente en web)
        if (!empty($validated['client_id'])) {
            $query->where('client_id', $validated['client_id']);
        }

        $query->orderBy('updated_at', 'desc');

        $orders = $query->paginate(10);

        return $orders;
    }
}