<?php

namespace App\Http\Requests\Category;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class ImportMemoriesRequest extends FormRequest
{
    private const ALLOWED_EXTENSIONS = ['md', 'txt'];

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
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->file('files', []) as $index => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $extension = strtolower($file->getClientOriginalExtension());

                if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    $validator->errors()->add(
                        "files.{$index}",
                        'Only .md and .txt files can be imported.',
                    );
                }
            }
        });
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
