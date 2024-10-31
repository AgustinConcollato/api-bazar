<?php

namespace App\Http\Controllers;
use App\Models\Categories;
use Illuminate\Support\Facades\Config;

class CategoriesController
{
    function categories()
    {
        $categories = Categories::with('subcategories')
            ->withCount('products')
            ->get();

        return response($categories);
    }

    public function category($code)
    {
        $category = Categories::with('subcategories')->where('category_code', $code)->first();

        if (!$category) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

        return response()->json($category);
    }

}