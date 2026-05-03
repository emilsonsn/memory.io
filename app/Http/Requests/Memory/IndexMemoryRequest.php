<?php

namespace App\Http\Requests\Memory;

use App\Enums\NoteColor;
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
            'color' => ['sometimes', 'nullable', Rule::in(NoteColor::values())],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => [
                'uuid',
                'distinct',
                Rule::exists('categories', 'id')->where('user_id', auth()->id()),
            ],
            'without_categories' => ['sometimes', 'boolean'],
            'sort_by' => ['sometimes', 'string', Rule::in([
                'title',
                'color',
                'due_date',
                'created_at',
                'updated_at',
            ])],
            'sort_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $categoryIds = $this->input('category_ids');
        $withoutCategories = $this->input('without_categories');

        if (is_string($categoryIds)) {
            $this->merge([
                'category_ids' => array_values(array_filter(array_map('trim', explode(',', $categoryIds)))),
            ]);
        }

        if (is_string($withoutCategories)) {
            $normalizedWithoutCategories = filter_var($withoutCategories, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($normalizedWithoutCategories !== null) {
                $this->merge([
                    'without_categories' => $normalizedWithoutCategories,
                ]);
            }
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('without_categories') && $this->has('category_ids')) {
                $validator->errors()->add(
                    'without_categories',
                    'The without categories filter cannot be combined with category ids.'
                );
            }
        });
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
