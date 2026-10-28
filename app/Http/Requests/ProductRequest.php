<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the checkbox fields before validating.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'track_stock' => $this->boolean('track_stock'),
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
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore(
                    $product instanceof Product ? $product->id : null,
                ),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('products', 'barcode')->ignore(
                    $product instanceof Product ? $product->id : null,
                ),
            ],
            'type' => ['required', Rule::enum(ProductType::class)],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'category_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'tax_rate_id' => ['nullable', 'integer', Rule::exists('tax_rates', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'track_stock' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
