<?php

namespace App\Http\Requests\Category;

use App\Enums\NoteColor;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class IndexCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'text' => ['sometimes', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', Rule::in(NoteColor::values())],
            'sort_by' => ['sometimes', 'string', Rule::in([
                'label',
                'color',
                'created_at',
                'updated_at',
            ])],
            'sort_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
