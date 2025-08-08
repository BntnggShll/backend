<?php

use App\Http\Controllers\ChangePasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProductsController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\ShipmentsController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ResellersController;
use App\Http\Controllers\LoginController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
Route::fallback(function () {
    return response()->json(['message' => 'API route not found.'], 404);
});
// Products
Route::get('/products', [ProductsController::class, 'index']);
Route::get('/stok', [ProductsController::class, 'stok']);
Route::get('/products/{id}', [ProductsController::class, 'show']);

// Shipments
Route::apiResource('/shipments', ShipmentsController::class);

// Payments
Route::post('/callback', [PaymentsController::class, 'callback']);

Route::post('/resellers', [ResellersController::class, 'store']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/cost', [ShipmentsController::class, 'cost']);
// routes/api.php
Route::get('/nama_daerah', function () {
    return \App\Models\ShippingRates::select('id', 'nama_daerah')->get();
});


Route::middleware('auth:sanctum')->group(function () {

    Route::post('/orders/{id}/cancel', [OrdersController::class, 'cancel']);
    Route::post('/update-user', [LoginController::class, 'updateUser']);
    Route::post('/orders', [OrdersController::class, 'store']);
    Route::get('/order-histori', [OrdersController::class, 'index']);
    Route::post('/change-password', [ChangePasswordController::class, 'update']);

});

//Register
Route::post('/customer/register', [RegisterController::class, 'register_customer']);

Route::get('/email/verify/{id}/{hash}', [RegisterController::class, 'verifyEmail'])
    ->middleware(['signed'])->name('verification.verify');

// Route::post('/email/resend-verification', [RegisterController::class, 'resendVerificationEmail'])
//     ->middleware('throttle:6,1')
//     ->name('verification.resend');