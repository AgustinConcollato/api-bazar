<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProviderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProviderController
{
    public function __construct(ProviderService $providerService)
    {
        $this->providerService = $providerService;
    }

    public function add(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'provider_code' => 'required|string|max:50|unique:providers,provider_code',
                'contact_info' => 'nullable|string|max:500',
            ]);

            $provide = $this->providerService->add($validated);

            return response()->json(['message' => 'Se agrego el provedor correctamente', 'provider' => $provide]);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al agregar un proveedor', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al agregar un proveedor', 'error' => $e->getMessage()], 500);
        }
    }

    public function get(Request $request)
    {
        $productId = $request->input('productId');
        $providerId = $request->input('providerId');

        if ($productId) {
            // buscar todos los proveedores del producto con el $productId
            $providers = $this->providerService->getProvidersByProduct($productId);

            return response()->json($providers);
        } else if ($providerId) {
            // buscar todos los productos del proveedor con el $providerId
            $products = $this->providerService->getProductsByProvider($providerId);

            return response()->json($products);
        } else {
            $providers = $this->providerService->getProviders();

            return response()->json($providers);
        }
    }

    public function assignProductToProvider(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'providers' => 'required|json',
            ]);

            $product = Product::find($validated['product_id']);

            $this->providerService->assignProductToProvider($validated, $product);

            $updatedProduct = Product::with('providers')->find($product->id);

            return response()->json([
                'message' => 'Producto actualizado con proveedores',
                'product' => $updatedProduct
            ], 200);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error al asignar un precio de compra', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al asignar un precio de compra', 'error' => $e->getMessage()], 500);
        }
    }
}