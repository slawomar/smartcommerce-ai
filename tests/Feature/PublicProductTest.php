<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_an_active_product(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Lampa publiczna',
                'sku' => 'LAMP-PUBLIC-001',
                'description' => 'Opis publicznego produktu.',
                'image_path' => 'products/lamp.jpg',
                'price' => '499.99',
                'stock' => 4,
                'is_active' => true,
            ]);

        $response = $this->get(
            route('catalog.show', $product),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('catalog/Show')
                    ->where('product.id', $product->id)
                    ->where('product.name', 'Lampa publiczna')
                    ->where('product.sku', 'LAMP-PUBLIC-001')
                    ->where(
                        'product.description',
                        'Opis publicznego produktu.',
                    )
                    ->where(
                        'product.image_path',
                        'products/lamp.jpg',
                    )
                    ->where('product.price', '499.99')
                    ->where('product.stock', 4),
            );
    }

    public function test_inactive_product_is_not_publicly_available(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()
            ->for($user)
            ->create([
                'is_active' => false,
            ]);

        $response = $this->get(
            route('catalog.show', $product),
        );

        $response->assertNotFound();
    }

    public function test_missing_product_returns_not_found(): void
    {
        $response = $this->get('/catalog/999999');

        $response->assertNotFound();
    }

    public function test_public_product_does_not_expose_private_fields(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()
            ->for($user)
            ->create([
                'is_active' => true,
            ]);

        $response = $this->get(
            route('catalog.show', $product),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'product',
                        fn (Assert $product) => $product
                            ->hasAll([
                                'id',
                                'name',
                                'sku',
                                'description',
                                'image_path',
                                'price',
                                'stock',
                                'created_at',
                            ])
                            ->missing('user_id')
                            ->missing('is_active')
                            ->missing('updated_at'),
                    ),
            );
    }
}
