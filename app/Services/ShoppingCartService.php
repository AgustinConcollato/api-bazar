<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderProducts;
use App\Models\Product;
use App\Models\ShoppingCart;
use ErrorException;

class ShoppingCartService
{
    protected $productService;
    protected $providerService;
    protected $paymentService;

    public function __construct(
        ProductService $productService,
        ProviderService $providerService,
        PaymentService $paymentService
    ) {
        $this->productService = $productService;
        $this->providerService = $providerService;
        $this->paymentService = $paymentService;
    }

    public function addProduct($clientId, $productId, $quantity)
    {
        $shoppingCart = ShoppingCart::where('client_id', $clientId)
            ->where('product_id', $productId)
            ->first();

        if ($shoppingCart) {
            $shoppingCart->quantity += $quantity;
            $shoppingCart->save();
            $shoppingCart->load('product');
            return $shoppingCart;
        }

        $shoppingCart = ShoppingCart::create([
            'client_id' => $clientId,
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
        $shoppingCart->load('product');
        return $shoppingCart;
    }

    public function getCartCount($clientId)
    {
        return ShoppingCart::where('client_id', $clientId)->count();
    }

    public function getCartDetail($client, $productService)
    {
        $shoppingCart = ShoppingCart::where('client_id', $client->id)
            ->with('product')
            ->get();

        $shoppingCart = $shoppingCart->filter(function ($item) {
            if ($item->product === null) {
                $item->delete();
                return false;
            }
            return true;
        });

        $clientType = $client ? $client->type : 'final';
        $shoppingCart->transform(function ($item) use ($clientType, $productService) {
            $item->product = $productService->applyCampaignDiscounts($item->product);
            $item->product->price = $item->product->getPriceForClient($clientType);
            unset($item->product->price_final);
            return $item;
        });

        return $shoppingCart;
    }

    public function updateProduct($clientId, $productId, $quantity)
    {
        $product = ShoppingCart::where('product_id', $productId)
            ->where('client_id', $clientId)
            ->first();

        $product->update(['quantity' => $quantity]);
        return $product;
    }

    public function deleteProduct($clientId, $productId)
    {
        $product = ShoppingCart::where('client_id', $clientId)
            ->where('product_id', $productId)
            ->first();

        $product->delete();
        return $product;
    }

    public function confirmOrder($validated)
    {
        $clientId = $validated['client_id'];
        $userName = $validated['user_name'];
        $comment = $validated['comment'];
        $address = $validated['address'];
        $orderDiscount = $validated['discount'];
        $delivery = $validated['delivery'];

        $cartItems = ShoppingCart::where('client_id', $clientId)->get();

        if ($cartItems->isEmpty()) {
            return ['error' => ['message' => 'El carrito está vacío'], 'status' => 400];
        }

        $client = Client::find($clientId);
        $clientType = $client ? $client->type : 'final';

        $calcPrice = function ($product, $quantity, $clientType) {
            $product = $this->productService->applyCampaignDiscounts($product);
            $priceForClient = $product->getPriceForClient($clientType);

            if ($product->campaign_discount) {
                $discountType = $product->campaign_discount['type'];
                $discountValue = $product->campaign_discount['value'];
                if ($discountType === 'percentage') {
                    $subtotal = $quantity * ($priceForClient - ($priceForClient * $discountValue / 100));
                    $discount = $discountValue;
                } else {
                    $subtotal = $quantity * max(0, $priceForClient - $discountValue);
                    $discount = 0;
                }
            } elseif ($product->discount) {
                $discount = $product->discount;
                $subtotal = $quantity * ($priceForClient - ($priceForClient * $discount) / 100);
            } else {
                $discount = 0;
                $subtotal = $quantity * $priceForClient;
            }
            return [$priceForClient, $subtotal, $discount];
        };


        $totalPrice = 0;
        foreach ($cartItems as $item) {
            $product = Product::find($item->product_id);
            list($priceForClient, $checkPrice, $discount) = $calcPrice($product, $item->quantity, $clientType);
            $totalPrice += $checkPrice;
        }

        if ($clientType !== 'final' && $totalPrice < 100000) {
            throw new ErrorException("Compra minima por la web es de $100.000", 422);
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
            $product = Product::findOrFail($item->product_id);

            $providers = $this->providerService->getProvidersByProduct($product->id);

            list($priceForClient, $subtotal, $discount) = $calcPrice($product, $item->quantity, $clientType);

            if (empty($providers)) {
                $estimatedPurchasePrice = ($priceForClient * 66) / 100;
            } else {
                $total = 0;
                $count = 0;
                foreach ($providers as $provider) {
                    if (isset($provider['purchase_price'])) {
                        $total += $provider['purchase_price'];
                        $count++;
                    }
                }
                $estimatedPurchasePrice = $count > 0 ? $total / $count : ($priceForClient * 66) / 100;
            }

            OrderProducts::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $priceForClient,
                'purchase_price' => $estimatedPurchasePrice,
                'discount' => $discount,
                'name' => $product->name,
                'picture' => $product->thumbnails,
                'subtotal' => $subtotal,
            ]);

            $order->update(['total_amount' => OrderProducts::where('order_id', $order->id)->sum('subtotal')]);
        }

        $order->update(['total_amount' => $order->total_amount + $delivery, 'delivery' => $delivery]);

        $surcharge = 0;
        if (isset($validated['payment_methods']) && array_key_exists('credit_card', $validated['payment_methods'])) {
            $surcharge = (($order->total_amount - $delivery) * 0.10);
            $order->update([
                'surcharge' => $surcharge,
                'total_amount' => $order->total_amount + $surcharge
            ]);
        } else {
            $order->update(['surcharge' => 0]);
        }

        ShoppingCart::where('client_id', $clientId)->delete();

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

        return [
            'message' => 'Pedido confirmado',
            'order_id' => $order->id,
            'payment_method' => $payments
        ];
    }
}