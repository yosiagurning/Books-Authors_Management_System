<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
}
