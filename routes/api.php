<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->get('csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products/{id}', [ProductController::class, 'detail']);
Route::get('/products', [ProductController::class, 'search']);

Route::put('/products/{id}', [ProductController::class, 'update']); // tiene que estar en auth
Route::delete('/products/{id}', [ProductController::class, 'delete']); // tiene que estar en auth
Route::post('/products', [ProductController::class, 'add']); // tiene que estar en auth

Route::get('/categories', [CategoriesController::class, 'categories']);
Route::get('/categories/{code}', [CategoriesController::class, 'category']);

Route::post('/order', [OrderController::class, 'create']);
Route::post('/order/product/add', [OrderController::class, 'add']);

Route::delete('/order/product/remove', [OrderController::class, 'remove']);
Route::delete('/order/cancel/{id}', [OrderController::class, 'cancel']);

Route::get('/order/pending', [OrderController::class, 'pending']);
Route::get('/order/pending/{id}', [OrderController::class, 'pending']);
Route::get('/order/completed', [OrderController::class, 'completed']);
Route::get('/order/{id}', [OrderController::class, 'products']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/firebase/users', [FirebaseController::class, 'users']);

});