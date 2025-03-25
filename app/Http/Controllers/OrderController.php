<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderProducts;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Config;

use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Request;

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
                'status' => 'required|string|in:pending,completed,cancelled,elaboration',
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

    public function pending($id = null)
    {
        if ($id) {
            $orders = Order::where('status', 'pending')
                ->where('client', $id)
                ->orderBy('created_at', 'asc')
                ->get();
        } else {
            $orders = Order::where('status', 'pending')
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return response()->json($orders);
    }

    public function completed(Request $request, $id = null)
    {

        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:' . date('Y'),
            'month' => 'nullable|integer|min:1|max:12', // El mes es opcional
        ]);

        $orders = $this->orderService->getCompleted($validated, $id);

        return response()->json($orders);
    }

    public function get($userId)
    {
        $orders = Order::where('client', $userId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($orders);
    }

    public function products($id)
    {
        $order = Order::with('products')->find($id);

        if (!$order) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

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
        $order->delete();

        return response()->json(Config::get('api-responses.success.deleted'));
    }

    public function complete($id)
    {
        $order = Order::find($id);
        $order->update(['status' => 'completed']);

        return response()->json(Config::get('api-responses.success.default'));
    }

    public function pdf(Request $request, $id)
    {
        $date = $request->input('date');

        $order = Order::with('products')->find($id);

        $data = [
            'client' => ['name' => $order->client_name, 'id' => $order->client],
            'code' => $order->id,
            'date' => date('d/m/Y', ($date / 1000)),
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
