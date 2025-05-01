<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ShoppingCartController;
use App\Http\Controllers\ClientAddressController;
use App\Http\Middleware\EnsureClient;
use App\Http\Middleware\EnsureClientOwnsResource;
use App\Http\Middleware\EnsureUser;
use Illuminate\Support\Facades\Route;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::get('/products/{id}', [ProductController::class, 'detail']);
Route::get('/products', [ProductController::class, 'search']);

Route::get('/categories', [CategoriesController::class, 'categories']);
Route::get('/categories/{code}', [CategoriesController::class, 'category']);

// rutas Order

Route::get('/order/pdf/{id}', [OrderController::class, 'pdf']);

Route::get('/order/pending/{id}', [OrderController::class, 'getOrdersPending']);

Route::get('/order/accepted/{id}', [OrderController::class, 'getOrdersAccepted']);

Route::get('/order/completed', [OrderController::class, 'completed']);
Route::get('/order/completed/{id}', [OrderController::class, 'completed']);

Route::get('/order/detail/{id}', [OrderController::class, 'detail']);

Route::get('/order/user/{userId}', [OrderController::class, 'get']);

// rutas Order

Route::post('/cart', [ShoppingCartController::class, 'add']);
Route::post('/cart/confirm', [ShoppingCartController::class, 'confirm']);

Route::get('/cart/{id}', [ShoppingCartController::class, 'get']);
Route::get('/cart/detail/{id}', [ShoppingCartController::class, 'getDetail']);

Route::put('/cart', [ShoppingCartController::class, 'update']);

Route::delete('/cart/{user}/{id}', [ShoppingCartController::class, 'delete']);

// Route::get('/clients/{id}', [ClientController::class, 'get']);
Route::post('/clients/register', [ClientController::class, 'register']);
Route::post('/clients/login', [ClientController::class, 'login']);

Route::get('/user/{userId}', [ClientAddressController::class, 'get']);
Route::post('/user', [ClientAddressController::class, 'add']);
Route::put('/user/{userId}', [ClientAddressController::class, 'update']);

Route::get('/products/related/{productId}', [ProductController::class, 'relatedProducts']);

// Route::get('/clients/auth', [ClientController::class, 'auth']);
Route::middleware(['web'])->get('/clients/auth', [ClientController::class, 'auth']);

Route::middleware(['auth:client', EnsureClient::class])->group(function () {

    Route::post('/clients/logout', [ClientController::class, 'logout']);
    Route::put('/clients/update/phone', [ClientController::class, 'updatePhone']);
    Route::delete('/address/{addressId}', [ClientAddressController::class, 'delete']);

    Route::post('/mail/verify', [ClientController::class, 'verifyEmail']);
    Route::post('/mail/verify-code', [ClientController::class, 'verifyCode']);

});


Route::middleware(['auth:sanctum', EnsureClientOwnsResource::class])->group(function () {

});

Route::middleware(['auth:sanctum', EnsureUser::class])->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);

    Route::get('/clients', [ClientController::class, 'get']);

    Route::get('/order/pending', [OrderController::class, 'getOrdersPending']);
    Route::get('/order/accepted', [OrderController::class, 'getOrdersAccepted']);

    Route::put('/order/complete/{id}', [OrderController::class, 'complete']);
    Route::put('/order/product', [OrderController::class, 'update']);

    Route::put('/order/accept/{id}', [OrderController::class, 'accept']);
    Route::put('/order/reject/{id}', [OrderController::class, 'reject']);

    Route::delete('/order/product/remove', [OrderController::class, 'remove']);

    Route::post('/products', [ProductController::class, 'add']);
    Route::post('/products/image-update/{id}', [ProductController::class, 'updateImages']);
    Route::post('/products/image-add/{id}', [ProductController::class, 'addImage']);

    Route::put('/products/{id}', [ProductController::class, 'update']);

    Route::delete('/products/image-delete/{id}', [ProductController::class, 'deleteImage']);
    Route::delete('/products/{id}', [ProductController::class, 'delete']);

    Route::post('/provider', [ProviderController::class, 'add']);
    Route::post('/provider/assign-product', [ProviderController::class, 'assignProductToProvider']);

    Route::get('/provider', [ProviderController::class, 'get']);

    Route::delete('/provider/{prodiverId}/product/{productId}', [ProviderController::class, 'deleteProductProvider']);

    Route::post('/order', [OrderController::class, 'create']);
    Route::post('/order/product/add', [OrderController::class, 'add']);

    Route::delete('/order/cancel/{id}', [OrderController::class, 'cancel']);

    Route::get('/analytics/net-profit', [AnalyticsController::class, 'netProfit']);

});