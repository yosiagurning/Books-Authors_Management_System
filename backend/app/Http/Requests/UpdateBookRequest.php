<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi payload untuk memperbarui Book.
 */
class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $bookId = $this->route('book')?->id ?? $this->route('book');

        return [
            'author_id' => ['required', 'integer', 'exists:authors,id'],
            'title' => ['required', 'string', 'max:255', 'regex:/.*\S.*/'],
            'description' => ['required', 'string', 'regex:/.*\S.*/'],
            'isbn' => ['required', 'string', 'regex:/^\d{13}$/'],
            'published_date' => ['required', 'date'],
            'page_count' => ['required', 'integer', 'min:1'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('books', 'slug')->ignore($bookId)],
        ];
    }

    /**
     * Tambahkan validasi duplikat book saat update.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $bookId = $this->route('book')?->id ?? $this->route('book');
                $isbn = trim((string) $this->input('isbn', ''));
                $title = trim((string) $this->input('title', ''));
                $authorId = (int) $this->input('author_id');
                $publishedDate = $this->input('published_date');

                $duplicateIsbn = Book::withTrashed()
                    ->whereKeyNot($bookId)
                    ->where('isbn', $isbn)
                    ->first();

                if ($duplicateIsbn) {
                    $validator->errors()->add(
                        'isbn',
                        $duplicateIsbn->trashed()
                            ? 'ISBN ini sudah digunakan oleh book yang ada di sampah. Pulihkan data lama jika diperlukan.'
                            : 'ISBN ini sudah digunakan oleh book lain.'
                    );

                    return;
                }

                $duplicateBook = Book::withTrashed()
                    ->whereKeyNot($bookId)
                    ->where('author_id', $authorId)
                    ->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
                    ->whereDate('published_date', $publishedDate)
                    ->first();

                if (! $duplicateBook) {
                    return;
                }

                $validator->errors()->add(
                    'title',
                    $duplicateBook->trashed()
                        ? 'Book dengan author, judul, dan tanggal terbit yang sama sudah ada di sampah.'
                        : 'Book dengan author, judul, dan tanggal terbit yang sama sudah ada.'
                );
            },
        ];
    }
}
