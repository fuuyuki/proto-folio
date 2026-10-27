<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the customers index lists customers', function () {
    Customer::factory()->create(['name' => 'Acme Corp']);

    $this->get(route('customers.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('customers/Index')
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Acme Corp')
            ->where('customers.data.0.invoices_count', 0)
        );
});

test('customers can be searched by name or email', function () {
    Customer::factory()->create(['name' => 'Globex', 'email' => 'billing@globex.test']);
    Customer::factory()->create(['name' => 'Initech', 'email' => 'billing@initech.test']);

    $this->get(route('customers.index', ['search' => 'globex']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Globex')
        );

    $this->get(route('customers.index', ['search' => 'initech.test']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Initech')
        );
});

test('a customer can be created', function () {
    $this->post(route('customers.store'), [
        'name' => 'Acme Corp',
        'email' => 'billing@acme.test',
        'phone' => '+1 555 0100',
        'address' => "1 Main St\nSpringfield",
    ])->assertRedirect(route('customers.index'));

    expect(Customer::query()->where('email', 'billing@acme.test')->exists())->toBeTrue();
});

test('a customer requires a name', function () {
    $this->post(route('customers.store'), ['name' => ''])
        ->assertSessionHasErrors('name');

    expect(Customer::query()->count())->toBe(0);
});

test('a customer email must be valid', function () {
    $this->post(route('customers.store'), [
        'name' => 'Acme Corp',
        'email' => 'not-an-email',
    ])->assertSessionHasErrors('email');
});

test('the edit page exposes the customer', function () {
    $customer = Customer::factory()->create();

    $this->get(route('customers.edit', $customer))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('customers/Edit')
            ->where('customer.id', $customer->id)
        );
});

test('a customer can be updated', function () {
    $customer = Customer::factory()->create(['name' => 'Old Name']);

    $this->put(route('customers.update', $customer), [
        'name' => 'New Name',
        'email' => $customer->email,
    ])->assertRedirect(route('customers.index'));

    expect($customer->refresh()->name)->toBe('New Name');
});

test('a customer can be deleted', function () {
    $customer = Customer::factory()->create();

    $this->delete(route('customers.destroy', $customer))
        ->assertRedirect(route('customers.index'));

    expect($customer->fresh())->toBeNull();
});

test('a customer with invoices cannot be deleted', function () {
    $invoice = Invoice::factory()->create();

    $this->delete(route('customers.destroy', $invoice->customer))
        ->assertRedirect();

    expect($invoice->customer->fresh())->not->toBeNull();
});
