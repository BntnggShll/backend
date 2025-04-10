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

// Products
// Route::apiResource('products', ProductsController::class);

// // Orders
// Route::apiResource('orders', OrdersController::class);

// // Order Items
// Route::apiResource('order-items', OrderItemsController::class);

// // Shipments
// Route::apiResource('shipments', ShipmentsController::class);

// // Payments
// Route::apiResource('payments', PaymentsController::class);

// Users
Route::apiResource('users', UserController::class);

//Register
Route::post('/sales/register', [RegisterController::class, 'register_sales']);
Route::post('/customer/register', [RegisterController::class, 'register_customer']);
Route::post('/reselles/register', [RegisterController::class, 'register_reseller']);

// // Sales
// Route::apiResource('sales', SalesController::class);

// // Sales Transactions
// Route::apiResource('sales-transactions', SalesTransactionsController::class);

// // Sales Tasks
// Route::apiResource('sales-tasks', SalesTasksController::class);

// // Resellers
// Route::apiResource('resellers', ResellersController::class);
