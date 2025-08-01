<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderProducts;
use App\Models\Product;
use App\Models\ShoppingCart;
use App\Services\PaymentService;
use App\Services\ProductService;
use App\Services\ProviderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShoppingCartController
{

    protected $paymentService;
    protected $providerService;
    protected $productService;

    public function __construct(
        PaymentService $paymentService,
        ProviderService $providerService,
        ProductService $productService
    ) {
        $this->paymentService = $paymentService;
        $this->providerService = $providerService;
        $this->productService = $productService;
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
            $shoppingCart->load('product');

            return response()->json($shoppingCart, 200);
        }

        $shoppingCart = ShoppingCart::create($data);
        $shoppingCart->load('product');

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

        // Aplicar descuentos de campañas activas a cada producto
        $shoppingCart->transform(function ($item) {
            $item->product = $this->productService->applyCampaignDiscounts($item->product);
            return $item;
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
                'discount' => 'nullable|numeric'
            ]);

            $clientId = $validated['client_id'];
            $userName = $validated['user_name'];
            $comment = $validated['comment'];
            $address = $validated['address'];
            $orderDiscount = $validated['discount'];

            $cartItems = ShoppingCart::where('client_id', $clientId)->get();

            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'El carrito está vacío'], 400);
            }

            $totalPrice = 0;
            foreach ($cartItems as $item) {
                $product = Product::find($item->product_id);

                // Aplicar descuentos de campañas activas
                $product = $this->productService->applyCampaignDiscounts($product);

                if ($product->campaign_discount) {
                    // Usar descuento de campaña
                    $discountType = $product->campaign_discount['type'];
                    $discountValue = $product->campaign_discount['value'];

                    if ($discountType === 'percentage') {
                        $checkPrice = $item->quantity * ($product->price - ($product->price * $discountValue / 100));
                    } else {
                        $checkPrice = $item->quantity * max(0, $product->price - $discountValue);
                    }
                } elseif ($product->discount) {
                    // Usar descuento del producto
                    $discount = $product->discount;
                    $checkPrice = $item->quantity * ($product->price - ($product->price * $discount) / 100);
                } else {
                    // Sin descuento
                    $checkPrice = $item->quantity * $product->price;
                }

                $totalPrice += $checkPrice;
            }

            if ($totalPrice < 100000) {
                throw new \Exception("Compra minima por la web es de $100.000", 422);
            }

            $order = Order::create([
                'client_id' => $clientId,
                'client_name' => $userName,
                'status' => 'pending',
                'total_amount' => 0,
                'comment' => $comment,
                'address' => json_encode($address),
                'discount' => $orderDiscount
            ]);

            foreach ($cartItems as $item) {
                $product = Product::find($item->product_id);

                $providers = $this->providerService->getProvidersByProduct($product->id);

                $product['providers'] = $providers;

                if (!$product) {
                    return response()->json(['message' => "El producto con ID {$item->product_id} no existe"], 404);
                }

                // Aplicar descuentos de campañas activas
                $product = $this->productService->applyCampaignDiscounts($product);

                if ($product->campaign_discount) {
                    // Usar descuento de campaña
                    $discountType = $product->campaign_discount['type'];
                    $discountValue = $product->campaign_discount['value'];

                    if ($discountType === 'percentage') {
                        $subtotal = $item->quantity * ($product->price - ($product->price * $discountValue / 100));
                        $discount = $discountValue; // Para guardar en la base de datos
                    } else {
                        $subtotal = $item->quantity * max(0, $product->price - $discountValue);
                        $discount = 0; // Para descuentos fijos, guardamos 0 en el campo discount
                    }
                } elseif ($product->discount) {
                    // Usar descuento del producto
                    $discount = $product->discount;
                    $subtotal = $item->quantity * ($product->price - ($product->price * $discount) / 100);
                } else {
                    // Sin descuento
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
                    'expected_amount' => ($order->total_amount - ($order->total_amount * $order->discount) / 100),
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
