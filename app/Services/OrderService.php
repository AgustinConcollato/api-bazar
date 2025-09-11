<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderProducts;
use App\Models\Product;

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
        $orderId = $validated['order_id'];

        $productInOrder = OrderProducts::where('order_id', $orderId)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($productInOrder) {
            throw new \Exception("El producto ya esta en la lista", 400);
        }

        $order = Order::findOrFail($orderId);

        $client = Client::findOrFail($order->client_id);

        $price = 0;

        $price = $client->type === 'final' ? $validated['price_final'] : $validated['price'];

        $quantity = $validated['quantity'];
        $discount = $validated['discount'];

        $subtotal = $discount ?
            ($price - ($price * $discount) / 100) * $quantity :
            $price * $quantity;

        $validated['subtotal'] = $subtotal;

        $product = OrderProducts::create($validated);

        if ($product) {
            $order = Order::find($validated['order_id']);
            $order->update(['total_amount' => $order->total_amount + $subtotal]);

            // Actualizar expected_amount del pago asociado considerando el descuento
            $payment = $order->payments()->first();
            if ($payment) {
                $totalWithoutDiscount = $order->total_amount;
                $discount = $order->discount ?? 0;
                $totalWithDiscount = $discount ? $totalWithoutDiscount - ($discount * $totalWithoutDiscount) / 100 : $totalWithoutDiscount;
                $payment->update(['expected_amount' => $totalWithDiscount]);
            }

            $p = Product::find($product->product_id);

            if (!$p) {
                $priceFinal = $client->type === 'final' ? $validated['price_final'] : $validated['price'];
            } else {
                $priceFinal = $p->getPriceForClient($client->type);
            }

            $product['price'] = $priceFinal;

            return $product;
        }
    }

    public function get($validated)
    {
        $query = Order::query()
            ->with('payments')
            ->with('products');

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

    public function updateOrderDiscount($validated)
    {
        $order = Order::findOrFail($validated['order_id']);

        // Actualizar el descuento del pedido
        $order->update(['discount' => $validated['discount']]);

        // Recalcular el total con descuento aplicado
        $totalWithoutDiscount = $order->total_amount;
        $discount = $validated['discount'];
        $totalWithDiscount = $discount > 0 ? $totalWithoutDiscount - ($discount * $totalWithoutDiscount) / 100 : $totalWithoutDiscount;

        // Actualizar expected_amount del pago asociado
        $payment = $order->payments()->first();
        if ($payment) {
            $payment->update(['expected_amount' => $totalWithDiscount]);
        }

        // Retornar el pedido actualizado
        $order = Order::with('products')->find($order->id);

        return [
            'order' => $order,
            'total_with_discount' => $totalWithDiscount
        ];
    }
}