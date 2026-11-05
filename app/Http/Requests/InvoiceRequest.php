<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Default the discount fields so older payloads keep working.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'discount_type' => $this->input('discount_type') ?? DiscountType::None->value,
            'discount_value' => $this->input('discount_value') ?? 0,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'discount_type' => ['required', Rule::enum(DiscountType::class)],
            'discount_value' => [
                'nullable',
                'numeric',
                'min:0',
                $this->input('discount_type') === DiscountType::Percent->value
                    ? 'max:100'
                    : 'max:9999999999.99',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one line item.',
            'items.min' => 'Add at least one line item.',
        ];
    }
}
