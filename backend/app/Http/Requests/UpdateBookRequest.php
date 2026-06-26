<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookId = $this->route('book')?->id ?? $this->route('book');

        return [
            'author_id' => ['required', 'integer', 'exists:authors,id'],
            'title' => ['required', 'string', 'max:255', 'regex:/.*\S.*/'],
            'description' => ['nullable', 'string'],
            'isbn' => ['nullable', 'string', 'max:32'],
            'published_date' => ['nullable', 'date'],
            'page_count' => ['nullable', 'integer', 'min:1'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('books', 'slug')->ignore($bookId)],
        ];
    }
}
