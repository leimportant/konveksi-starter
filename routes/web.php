<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;


Route::get('/', function () {
    return Inertia::render('Welcome');
})->middleware('guest')->name('welcome');

/**
 * Untuk user yang sudah login
 */

Route::get('/', function () {
    if (Auth::check()) {
        return Inertia::render('Home/Cart', [
            'customers' => Customer::all(['id', 'name']),
        ]);
    } else {
        return Inertia::render('Welcome');
    }
})->name('home');

Route::get('/home', function () {
    $user = Auth::user();

    $status = strtolower(trim($user->employee_status ?? 'customer'));

    if ($status === 'customer') {
        return Inertia::render('Home/Cart', [
            'customers' => Customer::all(['id', 'name']),
        ]);
    } else {
        return Inertia::render('Home/Staff');
    }

})->name('home.root');

Route::get('/reports/customer', function () {
    return Inertia::render('Home/ReportCustomer');
})->middleware(['auth'])->name('home.report-customer');

Route::get('/welcome', function () {
        return Inertia::render('Welcome');
});


// message route
Route::get('/messages', function () {
    return Inertia::render('Messages/ChatMessage');
})->middleware(['auth', 'verified'])->name('message.chat');

Route::get('/checkout', function () {
    return Inertia::render('Home/Checkout');
})->middleware(['auth', 'verified'])->name('checkout');

Route::get('dashboard', function () {
     $user = Auth::user();
    if ($user->employee_status === 'customer') {
        return Inertia::render('Home/Cart', [
            'customers' => Customer::all(['id', 'name']),
        ]);
    } else {
        return Inertia::render('Dashboard');
    }
    
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/shopping', function () {
    return Inertia::render('sales/Shopping');
})->name('Shopping');


Route::get('user', function () {
    return Inertia::render('user/Index');
})->middleware(['auth'])->name('user.getIndex');

Route::get('role', function () {
    return Inertia::render('role/Index');
})->middleware(['auth'])->name('role.getIndex');

Route::get('setting', function () {
    return Inertia::render('setting/Index');
})->middleware(['auth'])->name('setting.getIndex');

Route::get('/uoms', function () {
    return Inertia::render('uoms/Index');
})->middleware(['auth'])->name('uom.getIndex');

Route::get('/payment-methods', function () {
    return Inertia::render('payment-method/Index');
})->middleware(['auth'])->name('payment-method.getIndex');

Route::get('/sizes', function () {
    return Inertia::render('size/Index');
})->middleware(['auth'])->name('size.getIndex');

Route::get('/bank-accounts', function () {
    return Inertia::render('bank-account/Index');
})->middleware(['auth'])->name('bank-account.getIndex');

Route::get('/customers', function () {
    return Inertia::render('customer/Index');
})->middleware(['auth'])->name('customer.getIndex');

// Route::get('/home', function () {
//     return Inertia::render('home/Cart');
// })->middleware(['auth'])->name('home.cart');

Route::get('/price-types', function () {
    return Inertia::render('price-type/Index');
})->middleware(['auth'])->name('price-type.getIndex');

Route::get('/product-prices', function () {
    return Inertia::render('product-price/Index');
})->middleware(['auth'])->name('product-price.getIndex');

Route::get('/product-prices/{id}/edit', function ($id) {
    return Inertia::render('product-price/Update', [
        'id' => (int) $id,
    ]);
})->middleware(['auth'])->name('product-price.edit');

Route::get('/product-prices/create', function () {
    return Inertia::render('product-price/Create');
})->middleware(['auth'])->name('product-price.create');

Route::get('/konveksi', function () {
    return Inertia::render('konveksi/Index');
})->middleware(['auth'])->name('konveksi.getIndex');

Route::get('/notification', function () {
    return Inertia::render('notification/notification');
})->middleware(['auth'])->name('notification');

Route::get('/cash-balances', function () {
    return Inertia::render('cash-balance/Index');
})->middleware(['auth'])->name('cash-balance.index');

Route::get('/cash-balances/open-shift', function () {
    return Inertia::render('cash-balance/OpenShift');
})->middleware(['auth'])->name('cash-balance.openshift');


Route::get('/cash-balances/{id}/closing', function ($id) {
    return Inertia::render('cash-balance/Closing', [
        'id' => (int) $id,
    ]);
})->middleware(['auth'])->name('cash-balance.closing');


Route::get('/konveksi/model/create', function () {
    return Inertia::render('konveksi/CreateModel');
})->middleware(['auth'])->name('konveksi.create-model');


Route::get('/konveksi/model/{id}/edit', function ($id) {
    return Inertia::render('konveksi/UpdateModel', [
        'modelId' => (int) $id,
    ]);
})->middleware(['auth'])->name('konveksi.edit-model');

Route::get('/konveksi/model/list', function () {
    return Inertia::render('konveksi/ListModel');
})->middleware(['auth'])->name('konveksi.list-model');

Route::get('/sales', function () {
    return Inertia::render('sales/Index');
})->middleware(['auth'])->name('sale.getIndex');

Route::get('/pos', function () {
    return Inertia::render('sales/POS');
})->middleware(['auth'])->name('sales.POS');

Route::get('/products', function () {
    return Inertia::render('product/Index');
})->middleware(['auth'])->name('product.getIndex');

Route::get('/bahans', function () {
    return Inertia::render('bahan/Index');
})->middleware(['auth'])->name('bahans.getIndex');

Route::get('/categories', function () {
    return Inertia::render('category/Index');
})->middleware(['auth'])->name('categorie.getIndex');

Route::get('/slocs', function () {
    return Inertia::render('slocs/Index');
})->middleware(['auth'])->name('sloc.getIndex');

Route::get('/locations', function () {
    return Inertia::render('locations/Index');
})->middleware(['auth'])->name('location.getIndex');

Route::get('/stock-opnames', function () {
    return Inertia::render('stock-opnames/Index');
})->middleware(['auth'])->name('stock-opname.getIndex');
Route::get('/stock-opnames/create', function () {
    return Inertia::render('stock-opnames/Create');
})->middleware(['auth'])->name('stock-opnames.create');

Route::get('/stock-opnames/{id}/view', function ($id) {
    return Inertia::render('stock-opnames/View', [
    ]);
})->middleware(['auth'])->name('stock-opnames.view');

Route::get('/order/{order}/approve', function ($order) {
    return Inertia::render('order/Approve', [
        'order' => $order,
    ]);
})->name('order.approve');

Route::get('/order/{order}/reject', function ($order) {
    return Inertia::render('order/Reject', [
        'order' => $order,
    ]);
})->name('order.reject');

Route::get('/stock-opnames/{id}/view', function ($id) {
    return response()->json(['id' => (int) $id]);
})->middleware(['auth'])->name('stock-opnames.view');

Route::get('/good-receive', function () {
    return Inertia::render('good-receive/Index');
})->middleware(['auth'])->name('good-receive.getIndex');

Route::get('/good-receive/create', function () {
    return Inertia::render('good-receive/Create');
})->middleware(['auth'])->name('good-receive.create');

Route::get('/inventory', function () {
    return Inertia::render('inventory/Index');
})->middleware(['auth'])->name('inventory.getIndex');

Route::get('/inventory/stock-monitoring', function () {
    return Inertia::render('inventory/StockMonitoring');
})->middleware(['auth'])->name('inventory.stock-monitoring');

Route::get('/inventory/create', function () {
    return Inertia::render('inventory/Create');
})->middleware(['auth'])->name('inventory.create');


// transfer-stock
Route::get('/transfer-stock', function () {
    return Inertia::render('transfer-stock/Index');
})->middleware(['auth'])->name('transfer-stock.getIndex');

Route::get('/transfer-stock/create', function () {
    return Inertia::render('transfer-stock/Create');
})->middleware(['auth'])->name('transfer-stock.create');

Route::get('/transfer-stock/{id}/edit', function ($id) {
    return Inertia::render('transfer-stock/Update', [
        'id' =>  $id,
    ]);
})->middleware(['auth'])->name('transfer-stock.edit');

Route::get('/transfer-stock/{id}/view', function ($id) {
    return Inertia::render('transfer-stock/View', [
        'id' => $id,
    ]);
})->middleware(['auth'])->name('transfer-stock.view');

Route::get('/good-receive/{id}/edit', function ($id) {
    return Inertia::render('good-receive/Update', [
        'id' => (int) $id,
    ]);
})->middleware(['auth'])->name('good-receive.edit');

Route::get('/production/{activity_role}', function ($activity_role) {
    return Inertia::render('production/Index', [
        'activity_role' => $activity_role,
    ]);
})->middleware(['auth'])->name('production.getIndex');

Route::get('/order/{order}/approve', function ($order) {
    return Inertia::render('order/Approve', [
        'order' => $order,
    ]);
})->name('order.approve');

Route::get('/order/{order}/reject', function ($order) {
    return Inertia::render('order/Reject', [
        'order' => $order,
    ]);
})->name('order.reject');

// list order untuk admin
Route::get('/order-request', function () {
    return Inertia::render('order/OrderRequest');
})->name('order.request');

// list order untuk customer
Route::get('/order-history', function () {
    return Inertia::render('home/OrderHistory');
})->middleware(['auth'])->name('order.history');

Route::get('/production/{activity_role}/create', function ($activity_role) {
    return Inertia::render('production/Create', [
        'activity_role' => $activity_role,
    ]);
})->middleware(['auth'])->name('production.create');


Route::get('/production/{activity_role}/edit/{id}', function ($activity_role, $id) {
    return Inertia::render('production/Update', [
        'activity_role' => $activity_role,
        'id' => $id,
    ]);
})->middleware(['auth'])->name('production.update');

Route::get('/documents', function () {
    return Inertia::render('DocumentPage');
})->middleware(['auth'])->name('document.getIndex');

// Google Auth Routes
Route::get('auth/google', [App\Http\Controllers\Auth\GoogleController::class, 'redirectToGoogle'])->name('auth.google');
#Route::get('auth/google', 'App\Http\Controllers\Auth\GoogleController@redirectToGoogle');
Route::get('auth/google/callback', 'App\Http\Controllers\Auth\GoogleController@handleGoogleCallback');


Route::get('/production/{activity_role}/view/{id}', function ($activity_role, $id) {
    return Inertia::render('production/View', [
        'activity_role' => $activity_role,
        'id' => $id,
    ]);
})->middleware(['auth'])->name('production.view');

Route::get('/purchase-order', function () {
    return Inertia::render('purchase-order/Index');
})->middleware(['auth'])->name('purchase-order.getIndex');

Route::get('/purchase-order/create', function () {
    return Inertia::render('purchase-order/Create');
})->middleware(['auth'])->name('purchase-order.create');

Route::get('/purchase-order/{id}/edit', function ($id) {
    return Inertia::render('purchase-order/Update', [
        'purchaseOrderId' => $id,
    ]);
})->middleware(['auth'])->name('purchase-order.edit');

Route::get('/purchase-order/{id}/view', function ($id) {
    return Inertia::render('purchase-order/View', [
        'id' => $id,
    ]);
})->middleware(['auth'])->name('purchase-order.view');

Route::get('/reports/omset-per-payment', function () {
    return Inertia::render('reports/OmsetReport');
})->middleware(['auth'])->name('reports.omset-per-payment');


Route::get('/reports/omset-per-customer', function () {
    return Inertia::render('reports/OmsetPerCustomerReport');
})->middleware(['auth'])->name('reports.omset-per-customer');


Route::get('/reports/sales-summary', function () {
        return Inertia::render('reports/SalesSummary');
    })->middleware(['auth'])->name('reports.sales-summary');


Route::get('/reports/production-summary', function () {
        return Inertia::render('reports/ProductionSummary');
    })->middleware(['auth'])->name('reports.production-summary');

Route::get('/reports/production-detail', function () {
        return Inertia::render('reports/ProductionDetailReport');
    })->middleware(['auth'])->name('reports.production-detail');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/api.php';
