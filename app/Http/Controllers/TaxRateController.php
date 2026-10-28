<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaxRateRequest;
use App\Models\TaxRate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaxRateController extends Controller
{
    /**
     * Display a listing of the tax rates.
     */
    public function index(): Response
    {
        return Inertia::render('tax-rates/Index', [
            'taxRates' => TaxRate::query()
                ->withCount('products')
                ->orderBy('rate')
                ->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a tax rate.
     */
    public function create(): Response
    {
        return Inertia::render('tax-rates/Create');
    }

    /**
     * Store a newly created tax rate.
     */
    public function store(TaxRateRequest $request): RedirectResponse
    {
        TaxRate::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tax rate created.')]);

        return to_route('tax-rates.index');
    }

    /**
     * Show the form for editing the given tax rate.
     */
    public function edit(TaxRate $taxRate): Response
    {
        return Inertia::render('tax-rates/Edit', [
            'taxRate' => $taxRate->loadCount('products'),
        ]);
    }

    /**
     * Update the given tax rate.
     */
    public function update(TaxRateRequest $request, TaxRate $taxRate): RedirectResponse
    {
        $taxRate->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tax rate updated.')]);

        return to_route('tax-rates.index');
    }

    /**
     * Delete the given tax rate.
     */
    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        $taxRate->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tax rate deleted.')]);

        return to_route('tax-rates.index');
    }
}
