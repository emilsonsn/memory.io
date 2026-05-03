<?php

namespace App\Http\Requests\Memory;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class IndexMemoryRequest extends FormRequest
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
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date', 'after_or_equal:created_from'],
            'updated_from' => ['sometimes', 'date'],
            'updated_to' => ['sometimes', 'date', 'after_or_equal:updated_from'],
            'due_from' => ['sometimes', 'date'],
            'due_to' => ['sometimes', 'date', 'after_or_equal:due_from'],
            'text' => ['sometimes', 'string', 'max:255'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => [
                'uuid',
                'distinct',
                Rule::exists('categories', 'id')->where('user_id', auth()->id()),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $categoryIds = $this->input('category_ids');

        if (is_string($categoryIds)) {
            $this->merge([
                'category_ids' => array_values(array_filter(array_map('trim', explode(',', $categoryIds)))),
            ]);
        }
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
