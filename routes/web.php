<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('user', function () {
    return Inertia::render('user/Index');
})->middleware(['auth'])->name('user.index');

Route::get('role', function () {
    return Inertia::render('role/Index');
})->middleware(['auth'])->name('role.index');

Route::get('/uoms', function () {
    return Inertia::render('uoms/Index');
})->middleware(['auth'])->name('uoms.index');

Route::get('/payment-methods', function () {
    return Inertia::render('payment-method/Index');
})->middleware(['auth'])->name('payment-method.index');

Route::get('/sizes', function () {
    return Inertia::render('size/Index');
})->middleware(['auth'])->name('size.index');

Route::get('/price-types', function () {
    return Inertia::render('price-type/Index');
})->middleware(['auth'])->name('price-type.index');

Route::get('/product-prices', function () {
    return Inertia::render('product-price/Index');
})->middleware(['auth'])->name('product-price.index');
Route::get('/product-price/{id}/edit', function ($id) {
    return Inertia::render('product-price/Update', [
        'id' => (int) $id,
    ]);
})->middleware(['auth'])->name('product-price.edit');

Route::get('/konveksi', function () {
    return Inertia::render('konveksi/Index');
})->middleware(['auth'])->name('konveksi.index');


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
})->middleware(['auth'])->name('sales.index');

Route::get('/pos', function () {
    return Inertia::render('sales/POS');
})->middleware(['auth'])->name('sales.POS');

Route::get('/products', function () {
    return Inertia::render('product/Index');
})->middleware(['auth'])->name('products.index');

Route::get('/categories', function () {
    return Inertia::render('category/Index');
})->middleware(['auth'])->name('categories.index');

Route::get('/good-receive', function () {
    return Inertia::render('good-receive/Index');
})->middleware(['auth'])->name('good-receive.index');

Route::get('/good-receive/create', function () {
    return Inertia::render('good-receive/Create');
})->middleware(['auth'])->name('good-receive.create');


Route::get('/good-receive/{id}/edit', function ($id) {
    return Inertia::render('good-receive/Update', [
        'id' => (int) $id,
    ]);
})->middleware(['auth'])->name('good-receive.edit');

Route::get('/production/{activity_role}', function ($activity_role) {
    return Inertia::render('production/Index', [
        'activity_role' => $activity_role,
    ]);
})->middleware(['auth'])->name('production.index');

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


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/api.php';
