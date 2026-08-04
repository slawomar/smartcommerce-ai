<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $user->loadCount([
            'products',
            'products as active_products_count' => function ($query) {
                $query->where('is_active', true);
            },
            'products as low_stock_products_count' => function ($query) {
                $query
                    ->where('is_active', true)
                    ->where('stock', '<=', self::LOW_STOCK_THRESHOLD);
            },
        ]);

        $recentProducts = $user->products()
            ->latest()
            ->limit(5)
            ->get([
                'id',
                'name',
                'sku',
                'price',
                'stock',
                'is_active',
                'created_at',
            ]);

        return Inertia::render('Dashboard', [
            'productStats' => [
                'total' => $user->products_count,
                'active' => $user->active_products_count,
                'lowStock' => $user->low_stock_products_count,
                'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
            ],
            'recentProducts' => $recentProducts,
        ]);
    }
}
