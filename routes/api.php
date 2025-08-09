<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;

Route::get("/category/create/{name}", [CategoryController::class, 'create']);
Route::get("/category", [CategoryController::class, 'show']);
Route::get("/category/{slug}/create_product", [ProductController::class, 'create']);

Route::get("/product", [ProductController::class, 'show']);

Route::get("/product/search", [ProductController::class, 'search']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/logout',  [UserController::class, 'logout'])->middleware('auth:sanctum');


Route::post('/register', [UserController::class, 'register'])->name('register');
Route::post('/login', [UserController::class, 'login'])->name('login');
