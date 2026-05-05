<?php

namespace App\Http\Requests\Favorite;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreFavoriteRequest extends FormRequest
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
            'memory_id' => [
                'nullable',
                'uuid',
                'required_without:category_id',
                'prohibits:category_id',
                Rule::exists('memories', 'id')
                    ->where('user_id', auth()->id())
                    ->whereNull('deleted_at'),
            ],
            'category_id' => [
                'nullable',
                'uuid',
                'required_without:memory_id',
                'prohibits:memory_id',
                Rule::exists('categories', 'id')->where('user_id', auth()->id()),
            ],
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
