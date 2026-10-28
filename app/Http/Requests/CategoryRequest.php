<?php

namespace App\Http\Requests;

use App\Models\ProductCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Fill in the slug and normalize the flags before validating.
     */
    protected function prepareForValidation(): void
    {
        $slug = (string) $this->input('slug', '');
        $name = (string) $this->input('name', '');

        $this->merge([
            'slug' => Str::slug($slug !== '' ? $slug : $name),
            'sort_order' => $this->input('sort_order') ?? 0,
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
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories', 'slug')->ignore(
                    $category instanceof ProductCategory ? $category->id : null,
                ),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id'),
                Rule::notIn($category instanceof ProductCategory ? [$category->id] : []),
            ],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
        ];
    }
}
