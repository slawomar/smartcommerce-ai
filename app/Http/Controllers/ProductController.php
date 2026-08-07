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
    private const LOW_STOCK_THRESHOLD = 5;

    private const STATUS_FILTERS = [
        'active',
        'inactive',
    ];

    private const STOCK_FILTERS = [
        'available',
        'low',
        'out',
    ];

    private const SORT_FIELDS = [
        'created_at',
        'name',
        'price',
        'stock',
    ];

    private const SORT_DIRECTIONS = [
        'asc',
        'desc',
    ];

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $stock = trim((string) $request->query('stock', ''));
        $sort = trim((string) $request->query('sort', 'created_at'));
        $direction = trim((string) $request->query('direction', 'desc'));

        if (! in_array($status, self::STATUS_FILTERS, true)) {
            $status = '';
        }

        if (! in_array($stock, self::STOCK_FILTERS, true)) {
            $stock = '';
        }

        if (! in_array($sort, self::SORT_FIELDS, true)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, self::SORT_DIRECTIONS, true)) {
            $direction = 'desc';
        }

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
            ->when(
                $status === 'active',
                fn ($query) => $query->where('is_active', true),
            )
            ->when(
                $status === 'inactive',
                fn ($query) => $query->where('is_active', false),
            )
            ->when(
                $stock === 'available',
                fn ($query) => $query->where(
                    'stock',
                    '>',
                    self::LOW_STOCK_THRESHOLD,
                ),
            )
            ->when(
                $stock === 'low',
                fn ($query) => $query->whereBetween('stock', [
                    1,
                    self::LOW_STOCK_THRESHOLD,
                ]),
            )
            ->when(
                $stock === 'out',
                fn ($query) => $query->where('stock', 0),
            )
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'stock' => $stock,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'filterOptions' => [
                'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
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
