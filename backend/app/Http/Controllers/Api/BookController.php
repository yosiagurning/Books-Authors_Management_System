<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by')->toString() ?: 'title';
        $sortOrder = strtolower($request->string('sort_order')->toString() ?: 'asc');
        $perPage = min(max($request->integer('per_page', 10), 1), 50);
        $allowedSorts = ['title', 'created_at', 'updated_at', 'published_date', 'author'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'title';
        }

        if (! in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        $books = Book::query()
            ->with('author')
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('title', 'like', '%'.$request->string('search')->trim().'%')
            )
            ->when(
                $request->filled('author_id'),
                fn ($query) => $query->where('author_id', $request->integer('author_id'))
            )
            ->when(
                $request->filled('published_from'),
                fn ($query) => $query->whereDate('published_date', '>=', $request->input('published_from'))
            )
            ->when(
                $request->filled('published_to'),
                fn ($query) => $query->whereDate('published_date', '<=', $request->input('published_to'))
            )
            ->when($sortBy === 'author', function ($query) use ($sortOrder) {
                $query
                    ->join('authors', 'authors.id', '=', 'books.author_id')
                    ->select('books.*')
                    ->orderBy('authors.name', $sortOrder);
            }, fn ($query) => $query->orderBy($sortBy, $sortOrder))
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($books);
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = Book::create($this->normalizePayload($request->validated()));
        $book->load('author');

        return response()->json([
            'message' => 'Book berhasil dibuat.',
            'data' => $book,
        ], 201);
    }

    public function show(Book $book): JsonResponse
    {
        $book->load('author');

        return response()->json([
            'data' => $book,
        ]);
    }

    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $book->update($this->normalizePayload($request->validated(), $book));
        $book->load('author');

        return response()->json([
            'message' => 'Book berhasil diperbarui.',
            'data' => $book,
        ]);
    }

    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json([
            'message' => 'Book berhasil dihapus.',
        ]);
    }

    private function normalizePayload(array $validated, ?Book $book = null): array
    {
        $title = trim($validated['title']);
        $slug = trim($validated['slug'] ?? '');

        return [
            'author_id' => $validated['author_id'],
            'title' => $title,
            'slug' => $slug !== '' ? Str::slug($slug) : $this->generateUniqueSlug($title, $book?->id),
            'description' => isset($validated['description']) ? trim($validated['description']) : null,
            'isbn' => isset($validated['isbn']) ? trim($validated['isbn']) : null,
            'published_date' => $validated['published_date'] ?? null,
            'page_count' => $validated['page_count'] ?? null,
        ];
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value) ?: 'book';
        $slug = $baseSlug;
        $counter = 2;

        while (
            Book::query()
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
