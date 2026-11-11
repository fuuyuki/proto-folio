<?php

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard summarises billed, outstanding, and overdue invoices', function () {
    $this->actingAs(User::factory()->create());
    $customer = Customer::factory()->create(['name' => 'Acme']);

    Invoice::factory()->create([
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Paid,
        'issue_date' => today(),
        'due_date' => today()->addDays(14),
        'subtotal' => 100,
        'tax_amount' => 11,
        'total_amount' => 111,
    ]);

    $overdue = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Sent,
        'issue_date' => today()->subDays(2),
        'due_date' => today()->subDays(3),
        'subtotal' => 50,
        'tax_amount' => 5,
        'total_amount' => 55,
    ]);

    Invoice::factory()->create([
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft,
        'issue_date' => today(),
        'due_date' => today()->addDays(14),
        'total_amount' => 999,
    ]);

    $this->get(route('dashboard', ['range' => '7d']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('range', '7d')
            ->where('summary.billed.count', 2)
            ->where('summary.billed.total', fn ($total) => (float) $total === 166.0)
            ->where('summary.billed.net', fn ($net) => (float) $net === 150.0)
            ->where('summary.billed.tax', fn ($tax) => (float) $tax === 16.0)
            ->where('summary.billed.average', fn ($average) => (float) $average === 83.0)
            ->where('summary.issued', 3)
            ->where('summary.outstanding.count', 1)
            ->where('summary.outstanding.total', fn ($total) => (float) $total === 55.0)
            ->where('summary.outstanding.overdue_count', 1)
            ->where('summary.outstanding.overdue_total', fn ($total) => (float) $total === 55.0)
            ->where('draftCount', 1)
            ->has('trend', 7)
            ->has('overdueInvoices', 1)
            ->where('overdueInvoices.0.id', $overdue->id)
            ->where('overdueInvoices.0.customer', 'Acme')
            ->where('overdueInvoices.0.days_overdue', 3)
            ->has('recentInvoices', 3)
            ->has('topProducts', 0)
            ->has('topCustomers', 1)
            ->where('topCustomers.0.name', 'Acme')
            ->where('topCustomers.0.invoice_count', 2),
        );
});

test('an unknown range falls back to thirty days', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard', ['range' => 'year']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('range', '30d')
            ->has('trend', 30),
        );
});
