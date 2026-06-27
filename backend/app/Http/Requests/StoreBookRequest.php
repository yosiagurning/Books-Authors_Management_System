<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi payload untuk membuat Book.
 */
class StoreBookRequest extends FormRequest
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
        return [
            'author_id' => ['required', 'integer', 'exists:authors,id'],
            'title' => ['required', 'string', 'max:255', 'regex:/.*\S.*/'],
            'description' => ['required', 'string', 'regex:/.*\S.*/'],
            'isbn' => ['required', 'string', 'regex:/^\d{13}$/'],
            'published_date' => ['required', 'date'],
            'page_count' => ['required', 'integer', 'min:1'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('books', 'slug')],
        ];
    }

    /**
     * Tambahkan validasi duplikat book berdasarkan ISBN dan identitas utama katalog.
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

                $isbn = trim((string) $this->input('isbn', ''));
                $title = trim((string) $this->input('title', ''));
                $authorId = (int) $this->input('author_id');
                $publishedDate = $this->input('published_date');

                $duplicateIsbn = Book::withTrashed()
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
