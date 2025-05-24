<?php

namespace App\Http\Controllers;

use App\Services\CampaignsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CampaignController
{

    protected $campaignsService;

    public function __construct(CampaignsService $campaignsService)
    {
        $this->campaignsService = $campaignsService;
    }

    public function get(Request $request)
    {
        $stauts = $request->input("stauts") || false;
        $campaigns = $this->campaignsService->getCampaigns($stauts);
        return response()->json($campaigns);
    }

    public function getBySlug($slug)
    {
        $campaign = $this->campaignsService->getCampaignBySlug($slug);
        return response()->json($campaign);
    }

    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'discount_type' => 'nullable|string',
                'discount_value' => 'nullable|numeric',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'image'=> 'required|mimes:jpeg,png,jpg,webp,svg|max:2048',
            ]);

            $campaign = $this->campaignsService->createCampaign($validated, $request);
            return response()->json($campaign);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function addProducts(Request $request, $campaignId)
    {
        try {
            $validated = $request->validate([
                'products' => 'required|array',
                'products.*.product_id' => 'required|string|exists:products,id',
                'products.*.discount_type' => 'nullable|string|in:fixed,percentage',
                'products.*.discount_value' => 'nullable|numeric|min:0'
            ]);

            $products = $this->campaignsService->addProductsToCampaign($campaignId, $validated['products']);

            return response()->json($products);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateProduct(Request $request, $campaignId, $productId)
    {
        try {

            $product = $this->campaignsService->updateProductToCampaign($campaignId, $productId, $request->all());

            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteProduct($campaignId, $productId)
    {
        try {

            $product = $this->campaignsService->deleteProductToCampaign($campaignId, $productId);

            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $campaignId)
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'discount_type' => 'nullable|string',
                'discount_value' => 'nullable|numeric',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'is_active' => 'nullable|string',
                'image' => 'nullable|string'
            ]);

            $campaign = $this->campaignsService->updateCampaign($campaignId, $validated);
            return response()->json($campaign);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // public function delete($campaignId)
    // {
    //     try {
    //         $campaign = $this->campaignsService->deleteCampaign($campaignId);
    //         return response()->json($campaign);
    //     } catch (\Exception $e) {
    //         return response()->json(['errors'=> $e->getMessage()], 500);
    //     }
    // }
}
