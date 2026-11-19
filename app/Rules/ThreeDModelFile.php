<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ThreeDModelFile implements ValidationRule
{
    /**
     * Accepted model extensions.
     *
     * @var list<string>
     */
    private const EXTENSIONS = ['stl', '3mf'];

    /**
     * Validate that the uploaded file is an STL or 3MF model.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The model must be an uploaded STL or 3MF file.');

            return;
        }

        if (! in_array(
            strtolower($value->getClientOriginalExtension()),
            self::EXTENSIONS,
            true,
        )) {
            $fail('The model must be an STL or 3MF file.');
        }
    }
}
