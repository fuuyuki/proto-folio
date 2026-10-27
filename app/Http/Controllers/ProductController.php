<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $query = Product::query()->withCount('invoiceItems');

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return Inertia::render('products/Index', [
            'products' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show the form for creating a product.
     */
    public function create(): Response
    {
        return Inertia::render('products/Create');
    }

    /**
     * Store a newly created product.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created.')]);

        return to_route('products.index');
    }

    /**
     * Show the form for editing the given product.
     */
    public function edit(Product $product): Response
    {
        return Inertia::render('products/Edit', [
            'product' => $product->loadCount('invoiceItems'),
        ]);
    }

    /**
     * Update the given product.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);

        return to_route('products.index');
    }

    /**
     * Delete the given product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deleted.')]);

        return to_route('products.index');
    }
}
