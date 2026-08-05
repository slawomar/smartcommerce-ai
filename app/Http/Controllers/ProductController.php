<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $products = $request->user()
            ->products()
            ->select([
                'id',
                'name',
                'sku',
                'price',
                'stock',
                'is_active',
                'created_at',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('products/Create');
    }

    public function store(
        StoreProductRequest $request,
    ): RedirectResponse {
        $request->user()
            ->products()
            ->create($request->validated());

        return to_route('products.index');
    }

    public function edit(
        Request $request,
        Product $product,
    ): Response {
        abort_unless(
            $product->user_id === $request->user()->id,
            403,
        );

        return Inertia::render('products/Edit', [
            'product' => $product->only([
                'id',
                'name',
                'sku',
                'description',
                'price',
                'stock',
                'is_active',
            ]),
        ]);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
    ): RedirectResponse {
        $product->update($request->validated());

        return to_route('products.index');
    }

    public function destroy(
        Request $request,
        Product $product,
    ): RedirectResponse {
        abort_unless(
            $product->user_id === $request->user()->id,
            403,
        );

        $product->delete();

        return to_route('products.index');
    }
}
