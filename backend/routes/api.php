<?php

use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/summary', DashboardController::class);
Route::get('/authors/options', [AuthorController::class, 'options']);
Route::post('/authors/{authorId}/restore', [AuthorController::class, 'restore']);
Route::post('/books/{bookId}/restore', [BookController::class, 'restore']);
Route::apiResource('authors', AuthorController::class);
Route::apiResource('books', BookController::class);
