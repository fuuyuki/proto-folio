<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    /**
     * Display a listing of the units.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $query = Unit::query()
            ->withCount('products')
            ->with('baseUnit:id,name,code');

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->whereLike('name', "%{$search}%")
                    ->orWhereLike('code', "%{$search}%");
            });
        }

        return Inertia::render('units/Index', [
            'units' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show the form for creating a unit.
     */
    public function create(): Response
    {
        return Inertia::render('units/Create', [
            'units' => Unit::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Store a newly created unit.
     */
    public function store(UnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit created.')]);

        return to_route('units.index');
    }

    /**
     * Show the form for editing the given unit.
     */
    public function edit(Unit $unit): Response
    {
        return Inertia::render('units/Edit', [
            'unit' => $unit->loadCount('products'),
            'units' => Unit::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Update the given unit.
     */
    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit updated.')]);

        return to_route('units.index');
    }

    /**
     * Delete the given unit.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit deleted.')]);

        return to_route('units.index');
    }
}
