<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_public_catalog(): void
    {
        $response = $this->get(route('catalog.index'));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('catalog/Index')
                    ->has('products'),
            );
    }

    public function test_public_catalog_displays_only_active_products(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstActiveProduct = Product::factory()
            ->for($firstUser)
            ->create([
                'name' => 'Aktywna lampa',
                'is_active' => true,
            ]);

        $secondActiveProduct = Product::factory()
            ->for($secondUser)
            ->create([
                'name' => 'Aktywne biurko',
                'is_active' => true,
            ]);

        Product::factory()
            ->for($firstUser)
            ->create([
                'name' => 'Produkt nieaktywny',
                'is_active' => false,
            ]);

        $expectedIds = collect([
            $firstActiveProduct->id,
            $secondActiveProduct->id,
        ])
            ->sort()
            ->values()
            ->all();

        $response = $this->get(route('catalog.index'));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('products.total', 2)
                    ->has('products.data', 2)
                    ->where(
                        'products.data',
                        function ($products) use ($expectedIds): bool {
                            return $products
                                ->pluck('id')
                                ->sort()
                                ->values()
                                ->all() === $expectedIds;
                        },
                    ),
            );
    }

    public function test_public_catalog_does_not_expose_product_owner_data(): void
    {
        $user = User::factory()->create();

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Produkt publiczny',
                'is_active' => true,
            ]);

        $response = $this->get(route('catalog.index'));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'products.data.0',
                        fn (Assert $product) => $product
                            ->hasAll([
                                'id',
                                'name',
                                'sku',
                                'price',
                                'stock',
                                'created_at',
                            ])
                            ->missing('user_id')
                            ->missing('is_active')
                            ->missing('description'),
                    ),
            );
    }

    public function test_public_catalog_is_paginated(): void
    {
        $user = User::factory()->create();

        Product::factory()
            ->count(13)
            ->for($user)
            ->create([
                'is_active' => true,
            ]);

        Product::factory()
            ->count(2)
            ->for($user)
            ->create([
                'is_active' => false,
            ]);

        $response = $this->get(route('catalog.index'));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('products.current_page', 1)
                    ->where('products.per_page', 12)
                    ->where('products.total', 13)
                    ->has('products.data', 12)
                    ->where(
                        'products.next_page_url',
                        fn (?string $url): bool => $url !== null,
                    ),
            );
    }
}
