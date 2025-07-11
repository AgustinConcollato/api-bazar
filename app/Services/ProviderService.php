<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductProvider;
use App\Models\Provider;

class ProviderService
{
    public function add($validated)
    {
        $provider = Provider::create($validated);

        return $provider;
    }

    public function getProvidersByProduct($productId)
    {
        $product = Product::find($productId);

        return $product->providers;
    }

    public function getProductsByProvider($providerId)
    {
        $provider = Provider::find($providerId);

        $products = $provider->products()->paginate(20);

        return ['provider' => $provider, 'products' => $products];

    }

    public function getProviders()
    {
        $providers = Provider::all();

        $providers = $providers->map(function ($provider) {
            $response = $this->getProductsByProvider($provider->id);
            $products = $response['products']->total();

            $provider['products_count'] = $products;
            return $provider;
        });

        return $providers;
    }

    public function assignProductToProvider($validated, $product)
    {
        $providers = json_decode($validated['providers'], true);

        if (is_array($providers) && count($providers) > 0) {
            foreach ($providers as $providerId => $providerData) {
                // Soporta tanto formato antiguo (solo precio) como nuevo (objeto con purchase_price y provider_url)
                if (is_array($providerData)) {
                    $syncData = [
                        'purchase_price' => $providerData['purchase_price'] ?? null,
                        'provider_url' => $providerData['provider_url'] ?? null
                    ];
                } else {
                    $syncData = [
                        'purchase_price' => $providerData,
                        'provider_url' => null
                    ];
                }
                $product->providers()->syncWithoutDetaching([
                    $providerId => $syncData
                ]);
            }
        }
    }

    public function updateProductProvider($validated, $productProvider)
    {
        $productProvider->update($validated);

        return $productProvider;
    }

    public function deleteProductProvider($providerId, $productId)
    {
        $productProvider = ProductProvider::where('provider_id', $providerId)
            ->where('product_id', $productId)
            ->first();

        if ($productProvider) {
            $productProvider->delete();

            $providers = $this->getProvidersByProduct($productId);

            return $providers;
        }
    }

}