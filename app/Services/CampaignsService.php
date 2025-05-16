<?php

namespace App\Services;

use App\Models\Campaigns;

class CampaignsService
{


    public function getCampaigns()
    {
        return Campaigns::all();
    }

    public function getCampaignBySlug($slug)
    {
        return Campaigns::where('slug', $slug)->first();
    }

    public function createCampaign($data)
    {
        return Campaigns::create($data);
    }

    public function addProductsToCampaign($campaignId, $productIds)
    {
        $campaign = Campaigns::findOrFail($campaignId);
        $campaign->products()->syncWithoutDetaching($productIds);
    }

}