<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShoppingCartController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products/{id}', [ProductController::class, 'detail']);
Route::get('/products', [ProductController::class, 'search']);

Route::get('/categories', [CategoriesController::class, 'categories']);
Route::get('/categories/{code}', [CategoriesController::class, 'category']);

Route::post('/order', [OrderController::class, 'create']);
Route::post('/order/product/add', [OrderController::class, 'add']);

Route::delete('/order/cancel/{id}', [OrderController::class, 'cancel']);

Route::get('/order/pdf/{id}', [OrderController::class, 'pdf']);
Route::get('/order/pending', [OrderController::class, 'pending']);
Route::get('/order/pending/{id}', [OrderController::class, 'pending']);
Route::get('/order/completed', [OrderController::class, 'completed']);
Route::get('/order/{id}', [OrderController::class, 'products']);

Route::post('/cart', [ShoppingCartController::class, 'add']);
Route::post('/cart/confirm', [ShoppingCartController::class, 'confirm']);

Route::get('/cart/{id}', [ShoppingCartController::class, 'get']);

Route::put('/cart', [ShoppingCartController::class, 'update']);

Route::delete('/cart/{user}/{id}', [ShoppingCartController::class, 'delete']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/clients/{id}', [ClientController::class, 'get']);
    Route::get('/clients', [ClientController::class, 'get']);

    Route::post('/clients', [ClientController::class, 'add']);

    Route::get('/firebase/users', [FirebaseController::class, 'users']);

    Route::put('/order/complete/{id}', [OrderController::class, 'complete']);
    Route::put('/order/product', [OrderController::class, 'update']);

    Route::delete('/order/product/remove', [OrderController::class, 'remove']);

    Route::post('/products', [ProductController::class, 'add']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'delete']);
});