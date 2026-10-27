<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueDate = Carbon::instance(fake()->dateTimeBetween('-6 months', 'now'))->startOfDay();
        $subtotal = fake()->randomFloat(2, 50, 2000);
        $taxAmount = round($subtotal * 0.1, 2);

        return [
            'customer_id' => Customer::factory(),
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'status' => InvoiceStatus::Draft,
            'issue_date' => $issueDate,
            'due_date' => $issueDate->copy()->addDays(14),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the invoice has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
        ]);
    }

    /**
     * Indicate that the invoice has been sent to the customer.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Sent,
        ]);
    }
}
