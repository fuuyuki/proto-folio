<?php

namespace App\Http\Controllers;

use App\Actions\NumberSequences\GenerateSequenceNumber;
use App\Enums\ProductType;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;
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

        $query = Product::query()
            ->withCount('invoiceItems')
            ->with('category:id,name');

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->whereLike('name', "%{$search}%")
                    ->orWhereLike('sku', "%{$search}%");
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
        return Inertia::render('products/Create', $this->formOptions());
    }

    /**
     * Store a newly created product.
     */
    public function store(ProductRequest $request, GenerateSequenceNumber $generateNumber): RedirectResponse
    {
        $data = $request->validated();

        $data['sku'] = filled($data['sku'] ?? null)
            ? $data['sku']
            : $generateNumber->handle('product');

        Product::create($this->normalize($data));

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
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the given product.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['sku'] ?? null)) {
            $data['sku'] = $product->sku;
        }

        $product->update($this->normalize($data));

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

    /**
     * Normalize the validated payload before saving.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $data['cost_price'] = $data['cost_price'] ?? 0;

        if (($data['type'] ?? null) === ProductType::Service->value) {
            $data['track_stock'] = false;
        }

        return $data;
    }

    /**
     * The select options shared by the product form.
     *
     * @return array{
     *     categories: Collection<int, ProductCategory>,
     *     units: Collection<int, Unit>,
     *     taxRates: Collection<int, TaxRate>
     * }
     */
    private function formOptions(): array
    {
        return [
            'categories' => ProductCategory::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'units' => Unit::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'taxRates' => TaxRate::query()
                ->where('is_active', true)
                ->orderBy('rate')
                ->get(['id', 'name', 'rate']),
        ];
    }
}
