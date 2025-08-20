<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderProducts;
use App\Models\Product;
use App\Models\ShoppingCart;
use App\Services\PaymentService;
use App\Services\ProductService;
use App\Services\ProviderService;
use App\Services\ShoppingCartService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShoppingCartController
{

    protected $paymentService;
    protected $providerService;
    protected $productService;
    protected $shoppingCartService;

    public function __construct(
        PaymentService $paymentService,
        ProviderService $providerService,
        ProductService $productService,
        ShoppingCartService $shoppingCartService
    ) {
        $this->paymentService = $paymentService;
        $this->providerService = $providerService;
        $this->productService = $productService;
        $this->shoppingCartService = $shoppingCartService;
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|string',
            'quantity' => 'required|integer'
        ]);

        $client = $request->user('client');
        $shoppingCart = $this->shoppingCartService->addProduct($client->id, $data['product_id'], $data['quantity']);

        return response()->json($shoppingCart, $shoppingCart->wasRecentlyCreated ? 201 : 200);
    }

    public function get(Request $request)
    {
        $client = $request->user('client');
        $count = $this->shoppingCartService->getCartCount($client->id);

        return response()->json($count);
    }

    public function getDetail(Request $request)
    {
        $client = $request->user('client');
        $shoppingCart = $this->shoppingCartService->getCartDetail($client, $this->productService);

        return response()->json($shoppingCart);
    }

    public function update(Request $request)
    {
        $client = $request->user('client');
        $id = $request->input('product_id');
        $quantity = $request->input('quantity');

        $product = $this->shoppingCartService->updateProduct($client->id, $id, $quantity);

        return response()->json($product);
    }

    public function delete(Request $request, $id)
    {
        $client = $request->user('client');
        $product = $this->shoppingCartService->deleteProduct($client->id, $id);

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
                'discount' => 'nullable|numeric',
                'delivery' => 'nullable|numeric|min:0'
            ]);

            $result = $this->shoppingCartService->confirmOrder($validated);

            return response()->json($result);
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
