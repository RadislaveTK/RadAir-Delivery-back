<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get("/category/create/{name}", [CategoryController::class, 'create']);
Route::get("/category", [CategoryController::class, 'show']);
Route::get("/category/{slug}/create_product", [ProductController::class, 'create']);

Route::get("/product", [ProductController::class, 'show']);

Route::get("api/product/search", [ProductController::class, 'search']);


