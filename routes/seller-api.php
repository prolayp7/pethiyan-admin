<?php

use App\Http\Controllers\Api\Seller\SellerApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('seller')->name('seller.')->group(function () {
    Route::post('register', [SellerApiController::class, 'createSeller'])->name('register');
});
