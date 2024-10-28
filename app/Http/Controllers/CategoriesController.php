<?php

namespace App\Http\Controllers;
use App\Models\Categories;
use Illuminate\Support\Facades\Config;

class CategoriesController
{
    function allCategories()
    {
        $categories = Categories::with('subcategories')->get();

        return response($categories);
    }

    public function category($code)
    {
        // Buscar la categoría usando category_code y cargar las subcategorías relacionadas
        $category = Categories::with('subcategories')->where('category_code', $code)->first();

        // Verificar si la categoría existe
        if (!$category) {
            return response()->json(Config::get('api-responses.error.not_found'), 404);
        }

        return response()->json($category);
    }

}