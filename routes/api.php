<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\KasbonPaymentController;
use App\Http\Controllers\Api\UomController;
use App\Http\Controllers\Api\SizeController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\Api\ComboController;
use App\Http\Controllers\Api\ModelRefController;
use App\Http\Controllers\Api\ActivityRoleController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\GoodReceiveController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\ProductionController;
use App\Http\Controllers\Api\ApprovalServiceController;
use App\Http\Controllers\Api\PosProductController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PriceTypeController;
use App\Http\Controllers\Api\ProductPriceController;
use App\Http\Controllers\Api\SlocController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\StockOpnameController;

Route::put('/kasbon-payments/{kasbonPayment}', [KasbonPaymentController::class, 'update']);
Route::apiResource('kasbon-payments', KasbonPaymentController::class);

Route::middleware('auth')->group(function () {
    // User management
    Route::apiResource('api/users', UserController::class);
    Route::get('/user-menus', [MenuController::class, 'getUserMenus']);
    Route::get('api/combo/{key}', [ComboController::class, 'getComboData']);

    Route::apiResource('api/stock-opnames', StockOpnameController::class);
    Route::apiResource('api/roles', RoleController::class);
    Route::apiResource('api/dashboard', DashboardController::class);
    Route::apiResource('api/productions', ProductionController::class);
    Route::apiResource('api/uoms', UomController::class);
    Route::apiResource('api/slocs', SlocController::class);
    Route::apiResource('api/payment-methods', PaymentMethodController::class);
    Route::apiResource('api/sizes', SizeController::class);
    Route::apiResource('api/categories', CategoryController::class);
    Route::apiResource('api/products', ProductController::class);
    Route::get('api/products-search', [ProductController::class, 'productsBySearch']);
    Route::apiResource('api/good-receive', GoodReceiveController::class);
    Route::prefix('api/models')->group(function () {
        Route::get('/list', [ModelRefController::class, 'list']);
        Route::get('/', [ModelRefController::class, 'index']);
        Route::post('/', [ModelRefController::class, 'store']);
        Route::get('/{id}', [ModelRefController::class, 'show']);
        Route::put('/{id}', [ModelRefController::class, 'update']);
        Route::delete('/{id}', [ModelRefController::class, 'destroy']);
    });
    Route::apiResource('api/activity-roles', ActivityRoleController::class);
    Route::apiResource('pos-products', PosProductController::class);
    Route::apiResource('api/price-types', PriceTypeController::class);
    Route::apiResource('api/product-prices', ProductPriceController::class);
    Route::apiResource('api/locations', LocationController::class);
    Route::apiResource('api/inventories', InventoryController::class);
});

Route::prefix('approvals')->group(function () {
    Route::get('/pending', [ApprovalServiceController::class, 'getPendingApprovals']);
    Route::post('/{id}/approve', [ApprovalServiceController::class, 'approve']);
    Route::post('/{id}/reject', [ApprovalServiceController::class, 'reject']);
    Route::get('/history', [ApprovalServiceController::class, 'getApprovalHistory']);
});




