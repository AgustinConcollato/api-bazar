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
        $campaigns = $this->campaignsService->getCampaigns();
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
            $validared = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'discount_type' => 'nullable|string',
                'discount_value' => 'nullable|numeric',
                'start_date' => 'required|date',
                'end_date' => 'required|date'
            ]);

            $campaign = $this->campaignsService->createCampaign($validared);
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
                'product_ids' => 'required|array',
                'product_ids.*' => 'string|exists:products,id',
            ]);

            $products = $this->campaignsService->addProductsToCampaign($campaignId, $validated['product_ids']);

            return response()->json($products);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
