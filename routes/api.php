<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;

Route::get("/category/create/{name}", [CategoryController::class, 'create']);
Route::get("/category", [CategoryController::class, 'show']);
Route::get("/category/{slug}/create_product", [ProductController::class, 'create']);

Route::get("/product", [ProductController::class, 'show']);

Route::get("/product/search", [ProductController::class, 'search']);
