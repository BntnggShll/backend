<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProductsController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\OrderItemsController;
use App\Http\Controllers\ShipmentsController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SalesTransactionsController;
use App\Http\Controllers\SalesTasksController;
use App\Http\Controllers\ResellersController;
use App\Http\Controllers\LoginController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
// Products
Route::get('/products', [ProductsController::class, 'index']);
Route::get('/stok', [ProductsController::class, 'stok']);
Route::get('/products/{id}', [ProductsController::class, 'show']);

// Orders
Route::middleware('auth:sanctum')->post('/orders', [OrdersController::class, 'store']);
Route::middleware('auth:sanctum')->get('/order-histori', [OrdersController::class, 'index']);

// Order Items
Route::apiResource('/order-items', OrderItemsController::class);

// Shipments
Route::apiResource('/shipments', ShipmentsController::class);

// Payments
Route::post('/payment/create', [PaymentsController::class, 'create'])->name('payment.create');
// Route::post('/midtrans/callback', [PaymentsController::class, 'handleCallback']);
// Route::post('/midtrans/create-snap', [PaymentsController::class, 'createSnap']);
// Route::get('/midtrans/status/{orderId}', [PaymentsController::class, 'checkStatus']);
// Route::get('/midtrans/test', [PaymentsController::class, 'testPayment']);
Route::prefix('midtrans')->group(function () {
    Route::post('/create', [PaymentsController::class, 'createSnap']);
    Route::post('/callback', [PaymentsController::class, 'handleCallback']);
    Route::get('/status/{orderId}', [PaymentsController::class, 'checkStatus']);
});


// Users
Route::apiResource('/users', UserController::class);

//Register
Route::post('/customer/register', [RegisterController::class, 'register_customer']);

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return response()->json(['message' => 'Email berhasil diverifikasi!']);
})->middleware(['signed'])->name('verification.verify');

Route::post('/email/resend-verification', [RegisterController::class, 'resendVerificationEmail'])
    ->middleware('throttle:6,1') // Batasi agar tidak di-spam
    ->name('verification.resend');
// Sales
Route::apiResource('sales', SalesController::class);

// Sales Transactions
Route::apiResource('sales-transactions', SalesTransactionsController::class);

// Sales Tasks
Route::apiResource('sales-tasks', SalesTasksController::class);

// Resellers
Route::apiResource('resellers', ResellersController::class);

// Login
Route::middleware('auth:sanctum')->post('/update-user', [LoginController::class, 'updateUser']);

Route::post('/login', [LoginController::class, 'login']);


Route::post('/cost', [ShipmentsController::class, 'cost']);
// routes/api.php
Route::get('/nama_daerah', function () {
    return \App\Models\ShippingRates::select('id', 'nama_daerah')->get();
});


