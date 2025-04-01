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
            foreach ($providers as $providerId => $purchasePrice) {
                $product->providers()->syncWithoutDetaching([
                    $providerId => ['purchase_price' => $purchasePrice]
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