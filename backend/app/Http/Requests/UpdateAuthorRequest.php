<?php

namespace App\Http\Requests;

use App\Models\Author;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    /**
     * Tambahkan validasi duplikat author saat update.
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

                $authorId = $this->route('author')?->id ?? $this->route('author');
                $name = trim((string) $this->input('name', ''));
                $birthDate = $this->input('birth_date');
                $nationality = trim((string) $this->input('nationality', ''));

                $duplicate = Author::withTrashed()
                    ->whereKeyNot($authorId)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->whereDate('birth_date', $birthDate)
                    ->whereRaw('LOWER(nationality) = ?', [mb_strtolower($nationality)])
                    ->first();

                if (! $duplicate) {
                    return;
                }

                $validator->errors()->add(
                    'name',
                    $duplicate->trashed()
                        ? 'Data author yang sama sudah ada di sampah. Pulihkan data lama jika diperlukan.'
                        : 'Author dengan nama, tanggal lahir, dan nationality yang sama sudah ada.'
                );
            },
        ];
    }
}
