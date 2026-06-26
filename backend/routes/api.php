<?php

use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/summary', DashboardController::class);
Route::apiResource('authors', AuthorController::class);
Route::apiResource('books', BookController::class);
