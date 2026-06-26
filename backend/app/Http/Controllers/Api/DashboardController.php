<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Endpoint ringkasan dashboard (totals + item terbaru).
 */
class DashboardController extends Controller
{
    /**
     * Mengembalikan ringkasan dashboard yang di-cache untuk mengurangi beban query.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $payload = Cache::remember('dashboard.summary', now()->addSeconds(60), function () {
            return [
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
                        ->with(['author:id,name,slug'])
                        ->latest()
                        ->take(5)
                        ->get(),
                ],
            ];
        });

        return response()->json($payload);
    }
}
