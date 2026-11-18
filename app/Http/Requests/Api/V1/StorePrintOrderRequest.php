<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrintOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fulfillment' => ['required', Rule::in(['pickup', 'shipment'])],

            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['required', 'string', 'email', 'max:255'],
            'customer.whatsapp' => ['required', 'string', 'max:32', 'regex:/^[0-9+()\-\s]{7,32}$/'],
            'customer.note' => ['nullable', 'string', 'max:2000'],

            'shipping' => ['nullable', 'array'],
            'shipping.label' => ['required_if:fulfillment,shipment', 'nullable', 'string', 'max:100'],
            'shipping.address' => ['required_if:fulfillment,shipment', 'nullable', 'string', 'max:500'],
            'shipping.detail' => ['nullable', 'string', 'max:500'],
            'shipping.city' => ['required_if:fulfillment,shipment', 'nullable', 'string', 'max:100'],
            'shipping.province' => ['required_if:fulfillment,shipment', 'nullable', 'string', 'max:100'],
            'shipping.postal_code' => [
                'required_if:fulfillment,shipment',
                'nullable',
                'string',
                'regex:/^\d{5}$/',
            ],

            'model.name' => ['required', 'string', 'max:255'],
            'model.size' => ['nullable', 'string', 'max:100'],

            'print' => ['required', 'array'],
            'print.filament_name' => ['required', 'string', 'max:100'],
            'print.filament_color' => ['required', 'string', 'max:100'],
            'print.weight_g' => ['required', 'numeric', 'min:0.1', 'max:100000'],
            'print.print_hours' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'print.price' => ['required', 'numeric', 'min:1000', 'max:100000000'],

            'idempotency_key' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/'],

            // Hidden anti-spam field. Real browsers never fill it.
            'website' => ['prohibited'],
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
            'shipping.postal_code.regex' => 'The postal code must be five digits.',
            'customer.whatsapp.regex' => 'Enter a valid WhatsApp number.',
            'website.prohibited' => 'This request could not be processed.',
        ];
    }
}
