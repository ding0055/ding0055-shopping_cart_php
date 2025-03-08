<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
Route::get('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/cart/add-test-items', [CartController::class, 'addTestItems'])->name('cart.addTestItems');
Route::get('/cart/simulate-login', [CartController::class, 'simulateLogin'])->name('cart.simulateLogin');
Route::get('/cart/simulate-logout', [CartController::class, 'simulateLogout'])->name('cart.simulateLogout');
Route::post('/cart/update-all', [CartController::class, 'updateAll'])->name('cart.updateAll');
