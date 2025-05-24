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


    public function getCampaigns($stauts)
    {
        return $stauts ?
            Campaigns::where("is_active", $stauts)->get() :
            Campaigns::all();
    }

    public function getCampaignBySlug($slug)
    {
        return Campaigns::with('products')->where('slug', $slug)->first();
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
            $dataToSync[$product['product_id']] = [
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
        $name = $data['name'] ?? null;

        if ($startDate && $endDate && $endDate <= $startDate) {
            $validator = Validator::make([], []);
            $validator->errors()->add('end_date', 'La fecha de finalización debe ser posterior a la fecha de inicio');
            throw new ValidationException($validator);
        }

        if ($name) {
            $data['slug'] = Str::slug($name);
        }

        // Si se envía una nueva imagen en base64
        if (isset($data['image']) && str_starts_with($data['image'], 'data:image')) {
            // Eliminar la imagen anterior si existe
            if ($campaign->image) {
                Storage::disk('public')->delete($campaign->image);
            }
            
            // Procesar la imagen base64
            $image_parts = explode(";base64,", $data['image']);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            
            // Generar nombre único para la imagen
            $imageName = uniqid() . '.' . $image_type;
            $path = 'images/campaign/' . $imageName;
            
            // Guardar la imagen
            Storage::disk('public')->put($path, $image_base64);
            $data['image'] = $path;
        }

        $campaign->update($data);

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
