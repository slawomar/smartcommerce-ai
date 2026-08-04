<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('productStats')
                ->has('recentProducts')
            );
    }

    public function test_dashboard_displays_only_the_authenticated_users_product_statistics(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        Product::factory()
            ->for($user)
            ->create([
                'stock' => 20,
                'is_active' => true,
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'stock' => 5,
                'is_active' => true,
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'stock' => 0,
                'is_active' => false,
            ]);

        Product::factory()
            ->for($anotherUser)
            ->create([
                'stock' => 0,
                'is_active' => true,
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('productStats.total', 3)
                ->where('productStats.active', 2)
                ->where('productStats.lowStock', 1)
                ->where('productStats.lowStockThreshold', 5)
                ->has('recentProducts', 3)
            );
    }

    public function test_dashboard_displays_the_five_most_recent_products(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $products = collect(range(1, 6))
            ->map(function (int $number) use ($user): Product {
                $createdAt = now()->subDays(6 - $number);

                return Product::factory()
                    ->for($user)
                    ->create([
                        'name' => "Product {$number}",
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
            });

        Product::factory()
            ->for($anotherUser)
            ->create([
                'name' => 'Another user product',
                'created_at' => now()->addDay(),
                'updated_at' => now()->addDay(),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('recentProducts', 5)
                ->where('recentProducts.0.id', $products->get(5)->id)
                ->where('recentProducts.0.name', 'Product 6')
                ->where('recentProducts.4.id', $products->get(1)->id)
                ->where('recentProducts.4.name', 'Product 2')
            );
    }

    public function test_dashboard_returns_empty_statistics_for_user_without_products(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('productStats.total', 0)
                ->where('productStats.active', 0)
                ->where('productStats.lowStock', 0)
                ->where('productStats.lowStockThreshold', 5)
                ->has('recentProducts', 0)
            );
    }
}
