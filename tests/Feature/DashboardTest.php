<?php

use App\Enums\CashMovementType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\StockLevel;
use App\Models\User;
use Database\Seeders\PosDefaultsSeeder;
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
            ->where('topCustomers.0.invoice_count', 2)
            ->where('counter.has_sales', false)
            ->where('counter.open_shift', null)
            ->where('lowStock.has_data', false)
            ->has('lowStock.items', 0),
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

test('the dashboard reports counter sales and the open shift', function () {
    $this->actingAs(User::factory()->create());
    $this->seed(PosDefaultsSeeder::class);

    $register = Register::query()->firstOrFail();
    $currency = Currency::query()->where('code', 'IDR')->firstOrFail();
    $cash = PaymentMethod::query()->where('code', 'cash')->firstOrFail();

    $shift = Shift::query()->create([
        'register_id' => $register->id,
        'user_id' => auth()->id(),
        'opened_at' => today()->addHours(8),
        'opening_float' => 100,
        'status' => ShiftStatus::Open,
    ]);

    $sale = Sale::query()->create([
        'number' => 'SALE-0001',
        'location_id' => $register->location_id,
        'register_id' => $register->id,
        'shift_id' => $shift->id,
        'user_id' => auth()->id(),
        'currency_id' => $currency->id,
        'status' => SaleStatus::Completed,
        'total' => 250,
        'paid_total' => 250,
        'completed_at' => today()->addHours(10),
    ]);

    $sale->payments()->create([
        'payment_method_id' => $cash->id,
        'currency_id' => $currency->id,
        'type' => PaymentType::Payment,
        'amount' => 250,
        'paid_at' => today()->addHours(10),
    ]);

    $shift->cashMovements()->create([
        'type' => CashMovementType::Out,
        'amount' => 20,
        'reason' => 'Milk run',
        'user_id' => auth()->id(),
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('counter.has_sales', true)
            ->where('counter.count', 1)
            ->where('counter.total', fn ($total) => (float) $total === 250.0)
            ->where('counter.average', fn ($average) => (float) $average === 250.0)
            ->has('counter.hourly', 24)
            ->where('counter.hourly.10.total', fn ($total) => (float) $total === 250.0)
            ->where('counter.open_shift.register', 'Register 1')
            ->where('counter.open_shift.opening_float', fn ($float) => (float) $float === 100.0)
            ->where('counter.open_shift.cash_sales', fn ($cash) => (float) $cash === 250.0)
            ->where('counter.open_shift.expected_cash', fn ($cash) => (float) $cash === 330.0),
        );
});

test('the dashboard lists products at or below their reorder point', function () {
    $this->actingAs(User::factory()->create());
    $this->seed(PosDefaultsSeeder::class);

    $location = Location::query()->firstOrFail();

    $low = Product::factory()->create(['name' => 'Sugar', 'sku' => 'SKU-SUGAR']);
    $fine = Product::factory()->create(['name' => 'Coffee', 'sku' => 'SKU-COFFEE']);

    StockLevel::query()->create([
        'location_id' => $location->id,
        'product_id' => $low->id,
        'quantity_on_hand' => 2,
        'reorder_point' => 5,
    ]);

    StockLevel::query()->create([
        'location_id' => $location->id,
        'product_id' => $fine->id,
        'quantity_on_hand' => 40,
        'reorder_point' => 5,
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('lowStock.has_data', true)
            ->has('lowStock.items', 1)
            ->where('lowStock.items.0.name', 'Sugar')
            ->where('lowStock.items.0.sku', 'SKU-SUGAR')
            ->where('lowStock.items.0.quantity', fn ($value) => (float) $value === 2.0)
            ->where('lowStock.items.0.reorder_point', fn ($value) => (float) $value === 5.0),
        );
});
