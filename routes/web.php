<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/uoms', function () {
    return Inertia::render('uoms/Index');
})->middleware(['auth'])->name('uoms.index');


Route::get('/sizes', function () {
    return Inertia::render('size/Index');
})->middleware(['auth'])->name('size.index');

Route::get('/konveksi', function () {
    return Inertia::render('konveksi/Index');
})->middleware(['auth'])->name('konveksi.index');


Route::get('/konveksi/model/create', function () {
    return Inertia::render('konveksi/CreateModel');
})->middleware(['auth'])->name('konveksi.create-model');


Route::get('/konveksi/model/{id}/edit', function ($id) {
    return Inertia::render('konveksi/UpdateModel', [
        'modelId' => $id,
    ]);
})->middleware(['auth'])->name('konveksi.edit-model');

Route::get('/konveksi/model/list', function () {
    return Inertia::render('konveksi/ListModel');
})->middleware(['auth'])->name('konveksi.list-model');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/api.php';
