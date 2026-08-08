<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                'image_path',
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
        $data = $request->validated();

        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request
                ->file('image')
                ->store('products', 'public');
        }

        $request->user()
            ->products()
            ->create($data);

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
                'image_path',
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
        $data = $request->validated();

        $removeImage = (bool) ($data['remove_image'] ?? false);

        unset(
            $data['image'],
            $data['remove_image'],
        );

        if ($request->hasFile('image')) {
            $oldImagePath = $product->image_path;

            $data['image_path'] = $request
                ->file('image')
                ->store('products', 'public');

            $product->update($data);

            if ($oldImagePath !== null) {
                Storage::disk('public')->delete($oldImagePath);
            }

            return to_route('products.index');
        }

        if ($removeImage && $product->image_path !== null) {
            $oldImagePath = $product->image_path;

            $data['image_path'] = null;

            $product->update($data);

            Storage::disk('public')->delete($oldImagePath);

            return to_route('products.index');
        }

        $product->update($data);

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

        $imagePath = $product->image_path;

        $product->delete();

        if ($imagePath !== null) {
            Storage::disk('public')->delete($imagePath);
        }

        return to_route('products.index');
    }
}
