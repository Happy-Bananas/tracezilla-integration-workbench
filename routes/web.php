<?php

use App\Http\Controllers\ShopifyTestController;
use App\Http\Controllers\TracezillaTestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/shopify', [ShopifyTestController::class, 'show'])
    ->name('shopify.test');

Route::post('/shopify/test', [ShopifyTestController::class, 'test'])
    ->name('shopify.test.run');

Route::post('/shopify/list-products', [ShopifyTestController::class, 'listProducts'])
    ->name('shopify.products.run');

Route::get('/tracezilla', [TracezillaTestController::class, 'show'])
    ->name('tracezilla.test');


Route::post('/tracezilla/test', [TracezillaTestController::class, 'test'])
    ->name('tracezilla.test.run');

// the belowe code should to away
Route::post('/tracezilla/list-products', [TracezillaTestController::class, 'listProducts'])
    ->name('tracezilla.products.run');

Route::post('/tracezilla/list-skus', [TracezillaTestController::class, 'listSkus'])
    ->name('tracezilla.skus.run');

Route::post('/tracezilla/list-locations', [TracezillaTestController::class, 'listLocations'])
    ->name('tracezilla.locations.run');