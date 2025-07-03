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
use App\Http\Controllers\Api\TransferStockController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\Api\PosOrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CashBalanceController;
use App\Http\Controllers\Api\DocumentAttachmentController;
use App\Http\Controllers\Api\CartItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ChatMessageController;
use App\Http\Controllers\API\BankAccountController;

Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::get('api/orders/{order}/status/approved', [OrderController::class, 'approve']);
Route::get('api/orders/{order}/status/rejected', [OrderController::class, 'reject']);
Route::post('/order/reject', [OrderController::class, 'reject']);
Route::post('/order/{order}/upload-payment-proof', [OrderController::class, 'uploadPaymentProof']);

Route::middleware('auth')->group(function () {
    Route::get('api/orders/request', [OrderController::class, 'index']);
    Route::post('api/orders', [OrderController::class, 'store']);
    Route::put('api/orders/{order}', action: [OrderController::class, 'updateStatus']);
    Route::get('api/orders/customer', [OrderController::class, 'customerOrders']);
    Route::post('api/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);
    Route::get('api/orders/scan', [OrderController::class, 'scan']);
    Route::get('api/orders/{orderId}/shipping', [OrderController::class, 'checkShipping']);
    Route::post('api/orders/{orderId}/shipping', [OrderController::class, 'submitShipping']);
});

// // Route::put('/kasbon-payments/{kasbonPayment}', [KasbonPaymentController::class, 'update']);
// Route::apiResource('kasbon-payments', KasbonPaymentController::class);

Route::get('api/health', function () {
    return response()->json(['status' => 'ok']);
});


Route::middleware(['auth'])->group(function () {
    Route::post('api/push/subscribe', [PushController::class, 'subscribe']);
    Route::post('api/push/send', [PushController::class, 'send']);
});


Route::middleware('auth')->group(function () {

    Route::get('api/dashboard', [DashboardController::class, 'index']);
    Route::get('api/dashboard/sales', [DashboardController::class, 'getSalesData']);
    Route::get('api/dashboard/sales/amount', [DashboardController::class, 'getSalesByOmsetData']);
    // User management
    
    Route::apiResource('api/users', controller: UserController::class);

    Route::get('/user-menus', [MenuController::class, 'getUserMenus']);
    Route::get('/all-menus', [MenuController::class, 'getAllMenus']);

    Route::post('/roles/{role}/assign-menus', [RoleController::class, 'assignMenus']);
    Route::get('api/combo/{key}', [ComboController::class, 'getComboData']);

    Route::apiResource('api/transfer-stock', TransferStockController::class);
    Route::put('/api/transfer-stock/{transferId}/accept', [TransferStockController::class, 'accept']);
    Route::put('/api/transfer-stock/{transferId}/reject', [TransferStockController::class, 'reject']);

     Route::get('api/cash-balance', action: [CashBalanceController::class, 'index']); // Get list of cash balances
    Route::post('api/cash-balance/open', [CashBalanceController::class, 'openShift']); // Open a shift
    Route::put('api/cash-balance/{id}/close', [CashBalanceController::class, 'closeShift']); // Close a shift

    Route::get('api/chat/messages', [ChatMessageController::class, 'index']);
    Route::post('api/chat/send', [ChatMessageController::class, 'send']);
    Route::post('api/chat/messages/{id}/read', [ChatMessageController::class, 'markAsRead']);
    Route::get('api/chat/conversations', [ChatMessageController::class, 'conversations']);

    Route::apiResource('api/stock-opnames', StockOpnameController::class);
    Route::apiResource('api/roles', RoleController::class);
   
    Route::apiResource('api/productions', ProductionController::class);
    Route::apiResource('api/uoms', UomController::class);
    Route::apiResource('api/slocs', SlocController::class);
    Route::get('api/customers/search', [CustomerController::class, 'search']);
    Route::apiResource('api/customers', CustomerController::class);
    Route::apiResource('api/payment-methods', PaymentMethodController::class);
    Route::apiResource('api/sizes', SizeController::class);
    Route::apiResource('api/bank-account', BankAccountController::class);
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
    Route::get('api/locations/get', [LocationController::class, 'getLocations']);
    Route::get('api/stock', [InventoryController::class, 'getStock']);
    Route::apiResource('api/inventories', InventoryController::class);

    Route::post('api/pos/orders', [PosOrderController::class, 'placeOrder']);

    // Document Attachments
    Route::post('api/document-attachments/upload', [DocumentAttachmentController::class, 'upload']);
    Route::get('api/document-attachments', [DocumentAttachmentController::class, 'index']);
    Route::delete('api/document-attachments/{id}', [DocumentAttachmentController::class, 'destroy']);
    Route::get('api/document-attachments/{id}/view', [DocumentAttachmentController::class, 'viewAttachment']);

    Route::get('api/cart-items', [CartItemController::class, 'index']);
    Route::post('api/cart-items/add', [CartItemController::class, 'addToCart']); // Add to cart
    Route::delete('api/cart-items/{id}/remove', [CartItemController::class, 'removeFromCart']); // Remove from cart
    Route::delete('api/cart-items/clear', [CartItemController::class, 'clearCart']); // Clear cart

    Route::apiResource('api/settings', SettingController::class)->only(['index', 'update']);

    Route::get('api/reports/sales-summary', [ReportController::class, 'reportSalesSummary']);
    Route::get('api/reports/production-summary', [ReportController::class, 'reportProductionSummary']);
    Route::get('api/reports/omset-per-payment', [ReportController::class, 'reportOmsetPerPayment']);
    Route::get('api/setting/{key}', [SettingController::class, 'getData']);

});

Route::prefix('approvals')->group(function () {
    Route::get('/pending', [ApprovalServiceController::class, 'getPendingApprovals']);
    Route::post('/{id}/approve', [ApprovalServiceController::class, 'approve']);
    Route::post('/{id}/reject', [ApprovalServiceController::class, 'reject']);
    Route::get('/history', [ApprovalServiceController::class, 'getApprovalHistory']);
});




