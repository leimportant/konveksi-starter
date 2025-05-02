<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\KasbonPaymentController;
use App\Http\Controllers\Api\UomController;
use App\Http\Controllers\Api\SizeController;
use App\Http\Controllers\MenuController;

Route::put('/kasbon-payments/{kasbonPayment}', [KasbonPaymentController::class, 'update']);
Route::apiResource('kasbon-payments', KasbonPaymentController::class);

Route::middleware('auth')->group(function () {
    Route::get('/user-menus', [MenuController::class, 'getUserMenus']);
    Route::apiResource('api/uoms', UomController::class);
    Route::apiResource('api/sizes', SizeController::class);
});