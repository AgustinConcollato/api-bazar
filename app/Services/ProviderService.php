<?php

namespace App\Services;

use App\Models\Product;
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
                // Asociamos el proveedor con el producto en la tabla intermedia
                // $product->providers()->attach($providerId, ['purchase_price' => $purchasePrice]);
                $product->providers()->syncWithoutDetaching([
                    $providerId => ['purchase_price' => $purchasePrice]
                ]);
            }
        }

    }
}