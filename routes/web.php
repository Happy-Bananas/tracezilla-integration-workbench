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
Route::post('/shopify/list-locations', [ShopifyTestController::class, 'listLocations'])
    ->name('shopify.locations.run');
Route::delete('/shopify/credentials', [ShopifyTestController::class, 'forget'])
    ->name('shopify.credentials.forget');

Route::get('/tracezilla', [TracezillaTestController::class, 'show'])
    ->name('tracezilla.test');


Route::post('/tracezilla/test', [TracezillaTestController::class, 'test'])
    ->name('tracezilla.test.run');

Route::post('/tracezilla/list-skus', [TracezillaTestController::class, 'listSkus'])
    ->name('tracezilla.skus.run');
Route::delete('/tracezilla/credentials', [TracezillaTestController::class, 'forget'])
    ->name('tracezilla.credentials.forget');
