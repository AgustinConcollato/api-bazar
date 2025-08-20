<?php

namespace App\Services;

use App\Models\CampaignProduct;
use App\Models\Campaigns;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CampaignsService
{
    protected $productService;
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    public function getCampaigns($status)
    {
        return $status ?
            Campaigns::where("is_active", $status)->get() :
            Campaigns::all();
    }

    public function getActiveCampaigns()
    {
        $currentDate = now();

        return Campaigns::where('is_active', true)
            ->where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->get();
    }

    public function getCampaignBySlug($slug)
    {
        $campaign = Campaigns::where('slug', $slug)->first();
        $products = $campaign->products()
            ->paginate(20);

        $campaign->setRelation('products', $products);
        return $campaign;
    }

    public function getActiveCampaignBySlug($slug, $client)
    {
        $currentDate = now();

        // Buscar campaña sin productos todavía
        $campaign = Campaigns::where('slug', $slug)->first();

        if (!$campaign) {
            throw new \Exception('La campaña no existe');
        }

        // Verificar estado
        if (!$campaign->is_active && !$campaign->force_active) {
            throw new \Exception('La campaña no existe');
        }

        // Validar fechas si no está forzada
        if (!$campaign->force_active) {
            if ($currentDate < $campaign->start_date) {
                throw new \Exception('Esta campaña todavía no ha empezado');
            }

            if ($currentDate > $campaign->end_date) {
                throw new \Exception('Esta campaña ya finalizó');
            }
        }

        $products = $campaign->products()
            ->where('available_quantity', '>', 0)
            ->paginate(20);

        $clientType = $client ? $client->type : 'final';

        $products->getCollection()->transform(function ($product) use ($clientType) {
            $productClone = clone $product;

            $productClone = $this->productService->applyCampaignDiscounts($productClone);
            $productClone->price = $productClone->getPriceForClient($clientType);
            unset($productClone->price_final);

            return $productClone;
        });

        $campaign->setRelation('products', $products);

        return $campaign;
    }


    public function createCampaign($data, $request)
    {
        $image = $request->file('image');
        $path = $image->store('images/campaign', 'public');

        $data['image'] = $path;

        try {
            return Campaigns::create($data);
        } catch (\Illuminate\Database\QueryException  $e) {
            if ($e->errorInfo[1] == 1062) {
                throw new \Exception('Ya existe un evento con ese nombre');
            }
            throw $e;
        }
    }

    public function addProductsToCampaign($campaignId, $products)
    {
        $campaign = Campaigns::findOrFail($campaignId);

        $dataToSync = [];

        foreach ($products as $product) {
            $productId = $product['product_id'];

            // Verificar si el producto ya está en otra campaña (activa o inactiva)
            // Esto evita conflictos futuros cuando se activen las campañas
            $existingCampaign = Campaigns::where('id', '!=', $campaignId) // Excluir la campaña actual
                ->whereHas('products', function ($query) use ($productId) {
                    $query->where('product_id', $productId);
                })
                ->first();

            if ($existingCampaign) {
                $statusText = $existingCampaign->is_active ? 'activa' : 'inactiva';
                throw new \Exception("El producto con ID {$productId} ya está en la campaña '{$existingCampaign->name}' ({$statusText}). Un producto no puede estar en múltiples campañas al mismo tiempo.");
            }

            $dataToSync[$productId] = [
                'custom_discount_type' => $product['discount_type'] ?? $campaign->discount_type,
                'custom_discount_value' => $product['discount_value'] ?? $campaign->discount_value,
            ];
        }

        $campaign->products()->syncWithoutDetaching($dataToSync);

        $campaign = Campaigns::with(['products' => function ($query) {
            $query->select('products.*')
                ->selectRaw('campaign_product.custom_discount_type, campaign_product.custom_discount_value');
        }])->find($campaignId);

        return $campaign;
    }

    public function updateCampaign($campaignId, $data)
    {
        $campaign = Campaigns::findOrFail($campaignId);

        $startDate = $data['start_date'] ?? $campaign->start_date;
        $endDate = $data['end_date'] ?? $campaign->end_date;
        $isActive = isset($data['is_active']) ? $data['is_active'] : $campaign->is_active;
        $forceActive = isset($data['force_active'])
            ? filter_var($data['force_active'], FILTER_VALIDATE_BOOLEAN)
            : $campaign->force_active;

        // Si force_active está activo, anular fechas
        if ($forceActive) {
            $startDate = null;
            $endDate = null;
            $isActive = '0';
        } else {
            // Si force_active está en false y se quiere activar la campaña, validar fechas
            if ($isActive) {
                if (empty($startDate) || empty($endDate)) {
                    $validator = Validator::make([], []);
                    $validator->errors()->add('start_date', 'Se requiere una fecha de inicio');
                    $validator->errors()->add('end_date', 'Se requiere una fecha de finalización');
                    throw new ValidationException($validator);
                }

                if ($endDate <= $startDate) {
                    $validator = Validator::make([], []);
                    $validator->errors()->add('end_date', 'La fecha de finalización debe ser posterior a la fecha de inicio');
                    throw new ValidationException($validator);
                }
            }
        }

        // Verificar conflictos si se activa y no es force_active
        if ($isActive && !$campaign->is_active && empty($data['force_active'])) {
            $currentDate = now();
            $conflicts = [];

            foreach ($campaign->products as $product) {
                $conflictingCampaign = Campaigns::where('is_active', true)
                    ->where('start_date', '<=', $currentDate)
                    ->where('end_date', '>=', $currentDate)
                    ->where('id', '!=', $campaignId)
                    ->whereHas('products', function ($query) use ($product) {
                        $query->where('product_id', $product->id);
                    })
                    ->first();

                if ($conflictingCampaign) {
                    $conflicts[] = "El producto '{$product->name}' ya está en la campaña activa '{$conflictingCampaign->name}'";
                }
            }

            if (!empty($conflicts)) {
                throw new \Exception("No se puede activar la campaña. Conflictos encontrados:\n" . implode("\n", $conflicts));
            }
        }

        // Procesar imagen base64 (si se incluye)
        if (isset($data['image']) && str_starts_with($data['image'], 'data:image')) {
            if ($campaign->image) {
                Storage::disk('public')->delete($campaign->image);
            }

            $image_parts = explode(";base64,", $data['image']);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);

            $imageName = uniqid() . '.' . $image_type;
            $path = 'images/campaign/' . $imageName;
            Storage::disk('public')->put($path, $image_base64);

            $data['image'] = $path;
        }

        // Armar datos actualizados
        $updateData = [
            ...$data,
            'name' => $data['name'] ?? $campaign->name,
            'slug' => isset($data['name']) ? Str::slug($data['name']) : $campaign->slug,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => $isActive,
        ];

        if (isset($data['force_active'])) {
            $updateData['force_active'] = $data['force_active'];
        }

        if (isset($data['image'])) {
            $updateData['image'] = $data['image'];
        }

        $campaign->update($updateData);

        $campaign = $this->getCampaignBySlug($campaign->slug);

        return $campaign;
    }


    public function updateProductToCampaign($campaignId, $productId, $data)
    {
        $product = CampaignProduct::where('campaign_id', $campaignId)
            ->where('product_id', $productId)
            ->first();

        if (!$product) {
            throw new \Exception('Producto no encontrado en la campaña');
        }

        if (
            isset($data['custom_discount_value']) &&
            (!isset($data['custom_discount_type']) || empty($data['custom_discount_type'])) &&
            !$product->custom_discount_type
        ) {
            throw new \Exception('Debe seleccionar un tipo de descuento antes de establecer un valor');
        }

        $product->custom_discount_type = $data['custom_discount_type'] ?? $product->custom_discount_type;
        $product->custom_discount_value = $data['custom_discount_value'] ?? $product->custom_discount_value;

        $product->save();

        return $product;
    }

    public function deleteProductToCampaign($campaignId, $productId)
    {
        $product = CampaignProduct::where('campaign_id', $campaignId)
            ->where('product_id', $productId)
            ->first();

        if (!$product) {
            throw new \Exception('Producto no encontrado en la campaña');
        }

        $product->delete();

        $product->product();

        return $product;
    }
}
