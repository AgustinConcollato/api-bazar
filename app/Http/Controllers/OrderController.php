<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderProducts;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

class OrderController
{
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function create(Request $request)
    {

        try {
            $validated = $request->validate([
                'client_id' => 'required|string|exists:clients,id',
                'comment' => 'nullable|string|max:300',
                'status' => 'required|string|in:pending,completed,cancelled,accepted,rejected',
                'total_amount' => 'nullable|numeric|min:0',
                'client_name' => 'required|string|max:255'
            ]);

            $order = $this->orderService->create($validated);

            return response()->json($order, 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al crear un nuevo pedido', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear un nuevo pedido', 'errors' => $e->getMessage()], 500);
        }
    }

    public function get(Request $request)
    {
        try {

            $validated = $request->validate([
                'year' => 'nullable|integer|min:2000|max:' . date('Y'),
                'month' => 'nullable|integer|min:1|max:12',
                'status' => 'nullable|string|in:pending,completed,cancelled,accepted,rejected',
                'client_id' => 'nullable|string|exists:clients,id'
            ]);

            $user = $request->user('client');
            $id = $user->id ?? $validated['client_id'] ?? null;

            $validated['client_id'] = $id;

            $orders = $this->orderService->get($validated);

            return response()->json($orders);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al obtener los pedidos', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener los pedidos', 'error' => $e->getMessage()]);
        }
    }

    public function detail($id)
    {
        $order = Order::with('products')->find($id);

        if (!$order) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

        $payments = $order->payments;

        $order['payments'] = $payments;

        return response()->json($order);
    }

    public function add(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'purchase_price' => 'numeric|nullable',
                'price' => 'required|numeric',
                'order_id' => 'required|string',
                'product_id' => 'required|string',
                'picture' => 'nullable|string',
                'quantity' => 'required|integer',
                'discount' => 'nullable|integer'
            ]);

            $product = $this->orderService->add($validated);

            return response()->json($product, 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al agregar producto al pedido', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al agregar producto al pedido', 'error' => $e->getMessage()], 500);
        }
    }

    public function remove(Request $request)
    {

        $orderId = $request->input('orderId');
        $productId = $request->input('productId');

        $product = OrderProducts::where(['order_id' => $orderId])
            ->where('product_id', $productId)
            ->first();

        if (!$product) {
            return response()->json(Config::get('api-responses.error.not-found'), 404);
        }

        $order = Order::find($orderId);
        if ($order) {
            $order->update(['total_amount' => $order->total_amount - $product->subtotal]);
        }

        $product->delete();

        $order = Order::with('products')->find($order->id);

        return response()->json($order);
    }

    public function cancel($id)
    {
        $order = Order::find($id);
        $order->update(['status' => 'cancelled']);

        $orders = $this->orderService->get(['status' => 'accepted']);

        return response()->json($orders);
    }

    public function complete($id)
    {
        $order = Order::find($id);
        $order->update(['status' => 'completed']);

        return response()->json(Config::get('api-responses.success.default'));
    }

    public function accept($id)
    {
        $order = Order::find($id);
        $order->update(['status' => 'accepted']);

        return response()->json($order);
    }

    public function reject($id)
    {
        $order = Order::find($id);
        $order->update(['status' => 'rejected']);

        $orders = $this->orderService->get(['status' => 'pending']);

        return response()->json($orders);
    }

    public function pdf(Request $request, $id)
    {
        $date = $request->input('date');

        $order = Order::with('products')->find($id);

        $data = [
            'client' => ['name' => $order->client_name, 'id' => $order->client],
            'code' => $order->id,
            'date' => $order->updated_at->format('d/m/Y'),
            'products' => $order->products,
            'discount' => $order->discount,
            'total' => $order->total_amount
        ];

        $pdf = pdf::loadView('remit', $data);

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf');
    }

    public function update(Request $request)
    {
        $productId = $request->input('product_id');
        $orderId = $request->input('order_id');
        $name = $request->input('name');
        $price = $request->input('price');
        $quantity = $request->input('quantity');
        $discount = $request->input('discount');

        $product = OrderProducts::where(['product_id' => $productId])
            ->where(['order_id' => $orderId])
            ->first();

        if (!$product) {
            return response()->json(Config::get('api-responses.error.not-found'), 404);
        }

        $order = Order::find($orderId);

        if ($order) {
            $order->total_amount -= $product->subtotal;

            $subtotal = $discount
                ? ($price - ($discount * $price) / 100) * $quantity
                : $price * $quantity;

            $product->update([
                'name' => $name,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'discount' => $discount,
            ]);

            $order->total_amount += $product->subtotal;
            $order->save();
        }

        return response()->json($product);
    }
}
