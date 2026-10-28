<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the code, factor, and flag before validating.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtolower(trim((string) $this->input('code', ''))),
            'factor' => $this->input('factor') ?? 1,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:16',
                Rule::unique('units', 'code')->ignore(
                    $unit instanceof Unit ? $unit->id : null,
                ),
            ],
            'base_unit_id' => [
                'nullable',
                'integer',
                Rule::exists('units', 'id'),
                Rule::notIn($unit instanceof Unit ? [$unit->id] : []),
            ],
            'factor' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'is_active' => ['boolean'],
        ];
    }
}
