<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * API endpoints untuk mengelola data Author.
 */
class AuthorController extends Controller
{
    private const UNDO_WINDOW_SECONDS = 7;

    /**
     * Mengembalikan daftar author ringkas untuk dropdown/select (di-cache).
     */
    public function options(): JsonResponse
    {
        $authors = Cache::remember('authors.options', now()->addHours(6), function () {
            return Author::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
        });

        return response()->json([
            'data' => $authors,
        ]);
    }

    /**
     * Menampilkan daftar author dengan filter, sorting, dan pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $allowedSorts = ['name', 'created_at', 'updated_at', 'books_count'];

        $validated = $request->validate([
            'sort_by' => ['nullable', Rule::in($allowedSorts)],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'search' => ['nullable', 'string', 'max:120'],
            'has_books' => ['nullable', 'boolean'],
        ]);

        $sortBy = $validated['sort_by'] ?? 'name';
        $sortOrder = strtolower($validated['sort_order'] ?? 'asc');
        $perPage = (int) ($validated['per_page'] ?? 10);
        $search = isset($validated['search']) ? trim($validated['search']) : null;
        $hasBooks = $validated['has_books'] ?? null;

        $authors = Author::query()
            ->withCount('books')
            ->when(
                $search !== null && $search !== '',
                fn ($query) => $query->where('name', 'like', '%'.$search.'%')
            )
            ->when($hasBooks !== null, function ($query) use ($hasBooks) {
                $parsed = filter_var($hasBooks, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($parsed === true) {
                    $query->has('books');
                }

                if ($parsed === false) {
                    $query->doesntHave('books');
                }
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($authors);
    }

    /**
     * Membuat author baru.
     */
    public function store(StoreAuthorRequest $request): JsonResponse
    {
        $author = Author::create($this->normalizePayload($request->validated()));
        $author->loadCount('books');
        Cache::forget('authors.options');
        Cache::forget('dashboard.summary');

        return response()->json([
            'message' => 'Author berhasil dibuat.',
            'data' => $author,
        ], 201);
    }

    /**
     * Mengambil detail author beserta daftar book yang terkait.
     */
    public function show(Author $author): JsonResponse
    {
        $author->load([
            'books' => fn ($query) => $query->latest('created_at'),
        ])->loadCount('books');

        return response()->json([
            'data' => $author,
        ]);
    }

    /**
     * Memperbarui data author.
     */
    public function update(UpdateAuthorRequest $request, Author $author): JsonResponse
    {
        $author->update($this->normalizePayload($request->validated(), $author));
        $author->load([
            'books' => fn ($query) => $query->latest('created_at'),
        ])->loadCount('books');
        Cache::forget('authors.options');
        Cache::forget('dashboard.summary');

        return response()->json([
            'message' => 'Author berhasil diperbarui.',
            'data' => $author,
        ]);
    }

    /**
     * Menghapus author jika tidak memiliki book terkait.
     */
    public function destroy(Author $author): JsonResponse
    {
        if ($author->books()->exists()) {
            return response()->json([
                'message' => 'Author tidak dapat dihapus karena masih memiliki book.',
            ], 422);
        }

        $author->delete();
        Cache::forget('authors.options');
        Cache::forget('dashboard.summary');

        return response()->json([
            'message' => 'Author dipindahkan ke sampah. Anda dapat membatalkan dalam '.self::UNDO_WINDOW_SECONDS.' detik.',
            'data' => [
                'id' => $author->id,
                'undo_expires_at' => $this->undoExpiresAt($author)?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Memulihkan author yang baru saja di-soft delete selama masih dalam jendela undo.
     */
    public function restore(int $authorId): JsonResponse
    {
        $author = Author::withTrashed()->findOrFail($authorId);

        if (! $author->trashed()) {
            return response()->json([
                'message' => 'Author ini tidak sedang berada di sampah.',
            ], 422);
        }

        if (! $this->canUndo($author)) {
            return response()->json([
                'message' => 'Batas waktu undo untuk author ini sudah habis.',
            ], 410);
        }

        $author->restore();
        $author->load([
            'books' => fn ($query) => $query->latest('created_at'),
        ])->loadCount('books');
        Cache::forget('authors.options');
        Cache::forget('dashboard.summary');

        return response()->json([
            'message' => 'Author berhasil dipulihkan.',
            'data' => $author,
        ]);
    }

    /**
     * Normalisasi payload author agar konsisten (trim, slug otomatis).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
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

    /**
     * Menghasilkan slug unik untuk author.
     */
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

    /**
     * Menentukan apakah author masih bisa di-undo dalam jendela waktu yang tersedia.
     */
    private function canUndo(Author $author): bool
    {
        return $this->undoExpiresAt($author)?->isFuture() ?? false;
    }

    /**
     * Menghitung batas waktu undo author berdasarkan deleted_at.
     */
    private function undoExpiresAt(Author $author): ?Carbon
    {
        return $author->deleted_at?->copy()->addSeconds(self::UNDO_WINDOW_SECONDS);
    }
}
