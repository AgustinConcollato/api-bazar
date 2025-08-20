<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderProducts;
use App\Models\Product;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

class OrderController
{

    protected $orderService;
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
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los pedidos', 'error' => $e->getMessage()]);
        }
    }

    public function detail(Request $request, $id)
    {
        $order = Order::with('products')->findOrFail($id);

        $payments = $order->payments;
        $order['payments'] = $payments;

        $client = Client::find($order->client_id);
        // Obtener el tipo de cliente
        $clientType = $client ? $client->type : 'final';

        // Recalcular el precio de cada producto según el tipo de cliente
        foreach ($order->products as $orderProduct) {
            $product = Product::find($orderProduct->product_id);
            if ($product) {
                $orderProduct->price = $product->getPriceForClient($clientType);
            }
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

        // Actualizar expected_amount del pago asociado considerando el descuento
        $payment = $order->payments()->first();
        if ($payment) {
            $totalWithoutDiscount = $order->total_amount;
            $discount = $order->discount ?? 0;
            $totalWithDiscount = $discount ? $totalWithoutDiscount - ($discount * $totalWithoutDiscount) / 100 : $totalWithoutDiscount;
            $payment->update(['expected_amount' => $totalWithDiscount]);
        }

        return response()->json($order);
    }

    public function cancel($id)
    {
        $order = Order::find($id);

        // Eliminar los pagos asociados
        $order->payments()->delete();

        $order->update(['status' => 'cancelled']);

        $orders = $this->orderService->get(['status' => 'accepted']);

        return response()->json($orders);
    }

    public function complete($id)
    {
        $order = Order::find($id);

        // restar el valor de quantity de cada producto del pedido pero en la tabla de productos
        $p = null;
        foreach ($order->products as $product) {
            $p = Product::find($product->product_id);

            if ($p) {
                $p->update(['available_quantity' => $p->available_quantity - $product->quantity]);
            }
        }

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

        // Eliminar los pagos asociados
        $order->payments()->delete();

        $order->update(['status' => 'rejected']);

        $orders = $this->orderService->get(['status' => 'pending']);

        return response()->json($orders);
    }

    public function pdf($id)
    {
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

        return response($pdf->output(), 200)->header('Content-Type', 'application/pdf');
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

            $payment = $order->payments()->first();
            if ($payment) {
                $totalWithoutDiscount = $order->total_amount;
                $discount = $order->discount ?? 0;
                $totalWithDiscount = $discount ? $totalWithoutDiscount - ($discount * $totalWithoutDiscount) / 100 : $totalWithoutDiscount;
                $payment->update(['expected_amount' => $totalWithDiscount]);
            }
        }

        return response()->json($product);
    }

    public function updateOrderDiscount(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|string|exists:orders,id',
                'discount' => 'required|numeric|min:0|max:100'
            ]);

            $result = $this->orderService->updateOrderDiscount($validated);

            return response()->json([
                'message' => 'Descuento del pedido actualizado correctamente',
                'order' => $result['order'],
                'total_with_discount' => $result['total_with_discount']
            ]);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al actualizar el descuento del pedido', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el descuento del pedido', 'error' => $e->getMessage()], 500);
        }
    }
}
