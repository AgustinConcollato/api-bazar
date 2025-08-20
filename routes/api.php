<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ShoppingCartController;
use App\Http\Controllers\ClientAddressController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\EnsureClient;
use App\Http\Middleware\EnsureUser;

use Illuminate\Support\Facades\Route;


Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::get('/products/web', [ProductController::class, 'searchWeb']);
Route::get('/products/web/{id}', [ProductController::class, 'detailWeb']);
Route::get('/products/recent', [ProductController::class, 'searchRecent']);
Route::get('/products/related/{productId}', [ProductController::class, 'relatedProducts']);

Route::get('/categories', [CategoriesController::class, 'categories']);
Route::get('/categories/{code}', [CategoriesController::class, 'category']);

// rutas Order

Route::get('/order/pdf/{id}', [OrderController::class, 'pdf']);

Route::get('/order/completed', [OrderController::class, 'get']);

Route::get('/order/detail/{id}', [OrderController::class, 'detail']);

Route::get('/order/user/{userId}', [OrderController::class, 'get']);

// rutas Order

Route::post('/cart/confirm', [ShoppingCartController::class, 'confirm']);

Route::get('/cart', [ShoppingCartController::class, 'get']);
Route::get('/cart/detail', [ShoppingCartController::class, 'getDetail']);

Route::put('/cart', [ShoppingCartController::class, 'update']);


Route::post('/clients/register/panel', [ClientController::class, 'registerPanel']);
Route::post('/clients/register/web', [ClientController::class, 'registerWeb']);
Route::post('/clients/login', [ClientController::class, 'login']);

Route::get('/user/{userId}', [ClientAddressController::class, 'get']);
Route::post('/user', [ClientAddressController::class, 'add']);
Route::put('/user/{userId}', [ClientAddressController::class, 'update']);

Route::get('/campaigns/active', [CampaignController::class, 'getActiveCampaign']);
Route::get('/campaigns/active/{slug}', [CampaignController::class, 'getActiveCampaignBySlug']);

Route::post('/clients/send-email-reset-password', [ClientController::class, 'sendEmailResetPassword']);
Route::post('/clients/reset-password', [ClientController::class, 'resetPassword']);

//
//
//

Route::middleware(['web'])->get('/clients/auth', [ClientController::class, 'auth']);

//
// 
//

Route::middleware(['auth:client', EnsureClient::class])->group(function () {

    Route::post('/clients/logout', [ClientController::class, 'logout']);
    Route::put('/clients/update/phone', [ClientController::class, 'updatePhone']);
    Route::delete('/address/{addressId}', [ClientAddressController::class, 'delete']);

    Route::post('/mail/verify', [ClientController::class, 'verifyEmail']);
    Route::post('/mail/verify-code', [ClientController::class, 'verifyCode']);

    Route::get('/order/client', [OrderController::class, 'get']);

    Route::put('/clients/update/email', [ClientController::class, 'updateEmail']);

    Route::delete('/cart/{id}', [ShoppingCartController::class, 'delete']);
    Route::post('/cart', [ShoppingCartController::class, 'add']);
});

//
//
//

Route::get('/auth', [UserController::class, 'auth']);

Route::middleware(['auth:sanctum', EnsureUser::class])->group(function () {

    Route::post('/logout', [UserController::class, 'logout']);

    Route::post('/campaigns', [CampaignController::class, 'create']);
    Route::put('/campaigns/{campaignId}', [CampaignController::class, 'update']);
    Route::post('/campaigns/{campaignId}/products', [CampaignController::class, 'addProducts']);
    Route::put('/campaigns/{campaignId}/{productId}', [CampaignController::class, 'updateProduct']);
    Route::delete('/campaigns/{campaignId}/{productId}', [CampaignController::class, 'deleteProduct']);

    Route::get('/clients', [ClientController::class, 'get']);
    Route::get('/clients/{id}', [ClientController::class, 'get']);
    Route::put('/clients/{id}', [ClientController::class, 'update']);
    Route::get('/clients/search/{clientName}', [ClientController::class, 'search']);

    Route::get('/order', [OrderController::class, 'get']);

    Route::put('/order/complete/{id}', [OrderController::class, 'complete']);
    Route::put('/order/product', [OrderController::class, 'update']);

    Route::put('/order/accept/{id}', [OrderController::class, 'accept']);
    Route::put('/order/reject/{id}', [OrderController::class, 'reject']);
    Route::delete('/order/cancel/{id}', [OrderController::class, 'cancel']);

    Route::delete('/order/product/remove', [OrderController::class, 'remove']);

    Route::put('/order/update-discount', [OrderController::class, 'updateOrderDiscount']);

    Route::get('/products/panel', [ProductController::class, 'searchPanel']);
    Route::get('/products/{id}', [ProductController::class, 'detail']);

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

    Route::get('/analytics/net-profit', [AnalyticsController::class, 'netProfit']);
    Route::get('/analytics/compare-with-previous-month', [AnalyticsController::class, 'compareWithPreviousMonth']);
    Route::get('/analytics/resume', [AnalyticsController::class, 'resume']);

    Route::post('/payments', [PaymentController::class, 'createPayment']);
    Route::put('/payments/{id}', [PaymentController::class, 'updatePayment']);

    Route::post('/cash-register/create', [CashRegisterController::class, 'create']);
    Route::get('/cash-register', [CashRegisterController::class, 'get']);
    Route::get('/cash-register/{id}', [CashRegisterController::class, 'get']);
    Route::post('/cash-register/deposit', [CashRegisterController::class, 'deposit']);
    Route::post('/cash-register/withdraw', [CashRegisterController::class, 'withdraw']);
    Route::post('/cash-register/transfer-money', [CashRegisterController::class, 'transferMoney']);

    Route::get('/campaigns', [CampaignController::class, 'get']);
    Route::get('/campaigns/{slug}', [CampaignController::class, 'getBySlug']);
});
