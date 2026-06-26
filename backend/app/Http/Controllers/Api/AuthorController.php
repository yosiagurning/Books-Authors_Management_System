<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by')->toString() ?: 'name';
        $sortOrder = strtolower($request->string('sort_order')->toString() ?: 'asc');
        $perPage = min(max($request->integer('per_page', 10), 1), 50);
        $allowedSorts = ['name', 'created_at', 'updated_at', 'books_count'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }

        if (! in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        $authors = Author::query()
            ->withCount('books')
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->trim().'%')
            )
            ->when($request->filled('has_books'), function ($query) use ($request) {
                $hasBooks = filter_var($request->input('has_books'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($hasBooks === true) {
                    $query->has('books');
                }

                if ($hasBooks === false) {
                    $query->doesntHave('books');
                }
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($authors);
    }

    public function store(StoreAuthorRequest $request): JsonResponse
    {
        $author = Author::create($this->normalizePayload($request->validated()));
        $author->loadCount('books');

        return response()->json([
            'message' => 'Author berhasil dibuat.',
            'data' => $author,
        ], 201);
    }

    public function show(Author $author): JsonResponse
    {
        $author->load([
            'books' => fn ($query) => $query->latest('created_at'),
        ])->loadCount('books');

        return response()->json([
            'data' => $author,
        ]);
    }

    public function update(UpdateAuthorRequest $request, Author $author): JsonResponse
    {
        $author->update($this->normalizePayload($request->validated(), $author));
        $author->load([
            'books' => fn ($query) => $query->latest('created_at'),
        ])->loadCount('books');

        return response()->json([
            'message' => 'Author berhasil diperbarui.',
            'data' => $author,
        ]);
    }

    public function destroy(Author $author): JsonResponse
    {
        if ($author->books()->exists()) {
            return response()->json([
                'message' => 'Author tidak dapat dihapus karena masih memiliki book.',
            ], 422);
        }

        $author->delete();

        return response()->json([
            'message' => 'Author berhasil dihapus.',
        ]);
    }

    private function normalizePayload(array $validated, ?Author $author = null): array
    {
        $name = trim($validated['name']);
        $slug = trim($validated['slug'] ?? '');

        return [
            'name' => $name,
            'slug' => $slug !== '' ? Str::slug($slug) : $this->generateUniqueSlug($name, $author?->id),
            'bio' => isset($validated['bio']) ? trim($validated['bio']) : null,
            'birth_date' => $validated['birth_date'] ?? null,
            'nationality' => isset($validated['nationality']) ? trim($validated['nationality']) : null,
        ];
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value) ?: 'author';
        $slug = $baseSlug;
        $counter = 2;

        while (
            Author::query()
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
