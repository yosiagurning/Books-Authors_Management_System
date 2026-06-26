<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'totals' => [
                    'authors' => Author::count(),
                    'books' => Book::count(),
                ],
                'recent_authors' => Author::query()
                    ->withCount('books')
                    ->latest()
                    ->take(5)
                    ->get(),
                'recent_books' => Book::query()
                    ->with('author')
                    ->latest()
                    ->take(5)
                    ->get(),
            ],
        ]);
    }
}
