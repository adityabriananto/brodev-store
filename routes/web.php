<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SellerOrderController;
use Illuminate\Support\Facades\Route;

// Public Catalog Routes
Route::get('/', [ProductController::class, 'index'])->name('home');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// Guest Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Buyer Routes (Superadmin can also access via middleware)
    Route::middleware('buyer')->group(function () {
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
        Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');
        Route::get('/checkout', [OrderController::class, 'showCheckout'])->name('checkout.show');
        Route::post('/checkout', [OrderController::class, 'store'])->name('checkout.store');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/orders/{order}/payment-proof', [OrderController::class, 'uploadPaymentProof'])->name('orders.uploadPaymentProof');
    });

    // Seller Routes (Superadmin can also access via middleware)
    Route::middleware('seller')->group(function () {
        Route::get('/seller/dashboard', [SellerOrderController::class, 'dashboard'])->name('seller.dashboard');
        Route::get('/seller/products', [ProductController::class, 'sellerIndex'])->name('seller.products');
        Route::post('/seller/products', [ProductController::class, 'store'])->name('seller.store');
        Route::post('/seller/products/{product}', [ProductController::class, 'update'])->name('seller.update');
        Route::delete('/seller/products/{product}', [ProductController::class, 'destroy'])->name('seller.destroy');
        Route::get('/seller/orders', [SellerOrderController::class, 'index'])->name('seller.orders');
        Route::patch('/seller/orders/{order}/status', [SellerOrderController::class, 'updateStatus'])->name('seller.orders.updateStatus');
    });
});
