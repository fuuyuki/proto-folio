<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

dataset('module pages', [
    'dashboard' => ['dashboard', 'Dashboard'],
    'invoices' => ['invoices.index', 'invoices/Index'],
    'products' => ['products.index', 'products/Index'],
    'customers' => ['customers.index', 'customers/Index'],
    'expenses' => ['expenses.index', 'expenses/Index'],
    'documents' => ['documents.index', 'documents/Index'],
]);

it('renders a module page for authenticated users', function (string $route, string $component) {
    $this->actingAs(User::factory()->create())
        ->get(route($route))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
})->with('module pages');

it('redirects guests away from module pages', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with('module pages');
