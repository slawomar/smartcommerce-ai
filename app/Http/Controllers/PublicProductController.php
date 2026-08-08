<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class PublicProductController extends Controller
{
    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        return Inertia::render('catalog/Show', [
            'product' => $product->only([
                'id',
                'name',
                'sku',
                'description',
                'image_path',
                'price',
                'stock',
                'created_at',
            ]),
        ]);
    }
}
