<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Author;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * API endpoints untuk mengelola data Book.
 */
class BookController extends Controller
{
    private const UNDO_WINDOW_SECONDS = 7;

    /**
     * Menampilkan daftar book dengan filter, sorting, dan pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $allowedSorts = ['title', 'created_at', 'updated_at', 'published_date', 'author'];

        $validated = $request->validate([
            'sort_by' => ['nullable', Rule::in($allowedSorts)],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'search' => ['nullable', 'string', 'max:120'],
            'author_id' => ['nullable', 'integer', 'exists:authors,id'],
            'published_from' => ['nullable', 'date'],
            'published_to' => ['nullable', 'date', 'after_or_equal:published_from'],
        ]);

        $sortBy = $validated['sort_by'] ?? 'title';
        $sortOrder = strtolower($validated['sort_order'] ?? 'asc');
        $perPage = (int) ($validated['per_page'] ?? 10);
        $search = isset($validated['search']) ? trim($validated['search']) : null;
        $authorId = $validated['author_id'] ?? null;
        $publishedFrom = $validated['published_from'] ?? null;
        $publishedTo = $validated['published_to'] ?? null;

        $books = Book::query()
            ->with(['author:id,name,slug'])
            ->when(
                $search !== null && $search !== '',
                fn ($query) => $query->where('title', 'like', '%'.$search.'%')
            )
            ->when(
                $authorId !== null,
                fn ($query) => $query->where('author_id', $authorId)
            )
            ->when(
                $publishedFrom !== null,
                fn ($query) => $query->whereDate('published_date', '>=', $publishedFrom)
            )
            ->when(
                $publishedTo !== null,
                fn ($query) => $query->whereDate('published_date', '<=', $publishedTo)
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

    /**
     * Membuat book baru.
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = Book::create($this->normalizePayload($request->validated()));
        $book->load('author:id,name,slug');
        Cache::forget('dashboard.summary');

        return response()->json([
            'message' => 'Book berhasil dibuat.',
            'data' => $book,
        ], 201);
    }

    /**
     * Mengambil detail book berdasarkan id.
     */
    public function show(Book $book): JsonResponse
    {
        $book->load('author:id,name,slug');

        return response()->json([
            'data' => $book,
        ]);
    }

    /**
     * Memperbarui data book.
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $book->update($this->normalizePayload($request->validated(), $book));
        $book->load('author:id,name,slug');
        Cache::forget('dashboard.summary');

        return response()->json([
            'message' => 'Book berhasil diperbarui.',
            'data' => $book,
        ]);
    }

    /**
     * Menghapus book.
     */
    public function destroy(Book $book): JsonResponse
    {
        $book->delete();
        Cache::forget('dashboard.summary');

        return response()->json([
            'message' => 'Book dipindahkan ke sampah. Anda dapat membatalkan dalam '.self::UNDO_WINDOW_SECONDS.' detik.',
            'data' => [
                'id' => $book->id,
                'undo_expires_at' => $this->undoExpiresAt($book)?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Memulihkan book yang baru saja di-soft delete selama masih dalam jendela undo.
     */
    public function restore(int $bookId): JsonResponse
    {
        $book = Book::withTrashed()->findOrFail($bookId);

        if (! $book->trashed()) {
            return response()->json([
                'message' => 'Book ini tidak sedang berada di sampah.',
            ], 422);
        }

        if (! Author::query()->whereKey($book->author_id)->exists()) {
            return response()->json([
                'message' => 'Book tidak dapat dipulihkan karena author terkait sudah tidak tersedia.',
            ], 422);
        }

        if (! $this->canUndo($book)) {
            return response()->json([
                'message' => 'Batas waktu undo untuk book ini sudah habis.',
            ], 410);
        }

        $book->restore();
        $book->load('author:id,name,slug');
        Cache::forget('dashboard.summary');

        return response()->json([
            'message' => 'Book berhasil dipulihkan.',
            'data' => $book,
        ]);
    }

    /**
     * Normalisasi payload book agar konsisten (trim, slug otomatis).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
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

    /**
     * Menghasilkan slug unik untuk book.
     */
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

    /**
     * Menentukan apakah book masih bisa di-undo dalam jendela waktu yang tersedia.
     */
    private function canUndo(Book $book): bool
    {
        return $this->undoExpiresAt($book)?->isFuture() ?? false;
    }

    /**
     * Menghitung batas waktu undo book berdasarkan deleted_at.
     */
    private function undoExpiresAt(Book $book): ?Carbon
    {
        return $book->deleted_at?->copy()->addSeconds(self::UNDO_WINDOW_SECONDS);
    }
}
