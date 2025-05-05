<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderProducts;
use App\Models\Product;
use App\Models\ShoppingCart;
use App\Services\PaymentService;
use App\Services\ProviderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShoppingCartController
{
    public function __construct(PaymentService $paymentService, ProviderService $providerService)
    {
        $this->paymentService = $paymentService;
        $this->providerService = $providerService;
    }
    public function add(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|string',
            'product_id' => 'required|string',
            'quantity' => 'required|integer'
        ]);

        $shoppingCart = ShoppingCart::where('client_id', $data['client_id'])
            ->where('product_id', $data['product_id'])
            ->first();

        if ($shoppingCart) {
            $shoppingCart->quantity += $data['quantity'];
            $shoppingCart->save();

            return response()->json($shoppingCart, 200);
        }

        $shoppingCart = ShoppingCart::create($data);

        return response()->json($shoppingCart, 201);
    }

    public function get($id)
    {
        $shoppingCart = ShoppingCart::where('client_id', $id)
            ->with('product')
            ->get();

        $shoppingCart = $shoppingCart->filter(function ($item) {
            if ($item->product === null) {
                $item->delete();
                return false;
            }
            return true;
        });

        return response()->json($shoppingCart);
    }

    public function getDetail($id)
    {
        $shoppingCart = ShoppingCart::where('client_id', $id)->get();

        return response()->json($shoppingCart);
    }

    public function update(Request $request)
    {

        $id = $request->input('product_id');
        $quantity = $request->input('quantity');
        $client = $request->input('client_id');

        $product = ShoppingCart::where('product_id', $id)
            ->where('client_id', $client)
            ->first();

        $product->update(['quantity' => $quantity]);

        return response()->json($product);
    }


    public function delete($user, $id)
    {
        $product = ShoppingCart::where('client_id', $user)
            ->where('product_id', $id)
            ->first();

        $product->delete();

        return response()->json($product);
    }

    public function confirm(Request $request)
    {
        try {
            $validated = $request->validate([
                'client_id' => 'required|uuid|exists:clients,id',
                'user_name' => 'required|string|max:100',
                'comment' => 'nullable|string|max:300',
                'address' => 'required|array',
                'payment_methods' => 'required|array',
                'payment_methods.*' => 'numeric|min:0',
            ]);

            $clientId = $validated['client_id'];
            $userName = $validated['user_name'];
            $comment = $validated['comment'];
            $address = $validated['address'];
            $cartItems = ShoppingCart::where('client_id', $clientId)->get();

            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'El carrito está vacío'], 400);
            }

            foreach ($cartItems as $item) {
                $product = Product::find($item->product_id);

                if ($product->discount) {
                    $discount = $product->discount;
                    $subtotal = $item->quantity * ($product->price - ($product->price * $discount) / 100);
                } else {
                    $discount = 0;
                    $subtotal = $item->quantity * $product->price;
                }

                if ($subtotal < 150000) {
                    throw new \Exception("Compra minima por la web es de $150000", 422);
                }
            }

            $order = Order::create([
                'client_id' => $clientId,
                'client_name' => $userName,
                'status' => 'pending',
                'total_amount' => 0,
                'comment' => $comment,
                'address' => json_encode($address)
            ]);

            foreach ($cartItems as $item) {
                $product = Product::find($item->product_id);

                $providers = $this->providerService->getProvidersByProduct($product->id);

                $product['providers'] = $providers;

                if (!$product) {
                    return response()->json(['message' => "El producto con ID {$item->product_id} no existe"], 404);
                }

                if ($product->discount) {
                    $discount = $product->discount;
                    $subtotal = $item->quantity * ($product->price - ($product->price * $discount) / 100);
                } else {
                    $discount = 0;
                    $subtotal = $item->quantity * $product->price;
                }

                if (empty($providers)) {
                    // Si no hay proveedores, usar estimación
                    $estimatedPurchasePrice = ($product->price * 66) / 100;
                } else {
                    // Si hay proveedores, hacer el promedio de sus precios de compra
                    $total = 0;
                    $count = 0;

                    foreach ($providers as $provider) {
                        if (isset($provider['purchase_price'])) {
                            $total += $provider['purchase_price'];
                            $count++;
                        }
                    }

                    $estimatedPurchasePrice = $count > 0 ? $total / $count : ($product->price * 66) / 100;
                }

                OrderProducts::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $product->price,
                    'purchase_price' => $estimatedPurchasePrice,
                    'discount' => $discount,
                    'name' => $product->name,
                    'picture' => $product->thumbnails,
                    'subtotal' => $subtotal,
                ]);

                $order->update(['total_amount' => OrderProducts::where('order_id', $order->id)->sum('subtotal')]);
            }

            ShoppingCart::where('client_id', $clientId)->delete();

            // Agregar el método de pago al pedido ⬇️
            $payments = [];

            foreach ($validated['payment_methods'] as $method => $expectedAmount) {
                $data = [
                    'order_id' => $order->id,
                    'paid_amount' => 0,
                    'expected_amount' => $expectedAmount,
                    'method' => $method,
                    'paid_at' => null,
                ];

                $payment = $this->paymentService->createPayment($data);
                $payments[] = $payment;
            }


            return response()->json([
                'message' => 'Pedido confirmado',
                'order_id' => $order->id,
                'payment_method' => $payments
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de confirmar el pedido',
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al confirmar el pedido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}