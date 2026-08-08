<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class PublicCatalogController extends Controller
{
    public function __invoke(): Response
    {
        $products = Product::query()
            ->where('is_active', true)
            ->select([
                'id',
                'name',
                'sku',
                'image_path',
                'price',
                'stock',
                'created_at',
            ])
            ->latest()
            ->paginate(12);

        return Inertia::render('catalog/Index', [
            'products' => $products,
        ]);
    }
}
