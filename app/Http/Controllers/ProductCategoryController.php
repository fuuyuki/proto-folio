<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $query = ProductCategory::query()
            ->withCount('products')
            ->with('parent:id,name');

        if ($search !== '') {
            $query->whereLike('name', "%{$search}%");
        }

        return Inertia::render('categories/Index', [
            'categories' => $query->orderBy('sort_order')->orderBy('name')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show the form for creating a category.
     */
    public function create(): Response
    {
        return Inertia::render('categories/Create', [
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(CategoryRequest $request): RedirectResponse
    {
        ProductCategory::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return to_route('categories.index');
    }

    /**
     * Show the form for editing the given category.
     */
    public function edit(ProductCategory $category): Response
    {
        return Inertia::render('categories/Edit', [
            'category' => $category->loadCount('products'),
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the given category.
     */
    public function update(CategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return to_route('categories.index');
    }

    /**
     * Delete the given category.
     */
    public function destroy(ProductCategory $category): RedirectResponse
    {
        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return to_route('categories.index');
    }
}
