<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi payload untuk memperbarui Author.
 */
class UpdateAuthorRequest extends FormRequest
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
        $authorId = $this->route('author')?->id ?? $this->route('author');

        return [
            'name' => ['required', 'string', 'max:255', 'regex:/.*\S.*/'],
            'bio' => ['required', 'string', 'regex:/.*\S.*/'],
            'birth_date' => ['required', 'date'],
            'nationality' => ['required', 'string', 'max:100', 'regex:/.*\S.*/'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('authors', 'slug')->ignore($authorId)],
        ];
    }
}
