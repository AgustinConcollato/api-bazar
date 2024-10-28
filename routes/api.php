<?php

use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->get('/csrf-token', function (Request $request) {
    return response()->json(['token' => csrf_token()]);
});

Route::get('/products/{id}', [ProductController::class, 'detail']);
Route::get('/products', [ProductController::class, 'search']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::post('/products', [ProductController::class, 'addProduct']);

Route::get('/categories', [CategoriesController::class, 'allCategories']);
Route::get('/categories/{code}', [CategoriesController::class, 'category']);

