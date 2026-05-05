<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class PreviewImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                File::image()
                    ->max(5120)
                    ->dimensions(
                        Rule::dimensions()->maxWidth(8000)->maxHeight(8000)
                    ),
            ],
        ];
    }
}
