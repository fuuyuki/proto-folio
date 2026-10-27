<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $query = Customer::query()->withCount('invoices');

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return Inertia::render('customers/Index', [
            'customers' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show the form for creating a customer.
     */
    public function create(): Response
    {
        return Inertia::render('customers/Create');
    }

    /**
     * Store a newly created customer.
     */
    public function store(CustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer created.')]);

        return to_route('customers.index');
    }

    /**
     * Show the form for editing the given customer.
     */
    public function edit(Customer $customer): Response
    {
        return Inertia::render('customers/Edit', [
            'customer' => $customer->loadCount('invoices'),
        ]);
    }

    /**
     * Update the given customer.
     */
    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer updated.')]);

        return to_route('customers.index');
    }

    /**
     * Delete the given customer.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->invoices()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Customers with invoices cannot be deleted.'),
            ]);

            return back();
        }

        $customer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer deleted.')]);

        return to_route('customers.index');
    }
}
