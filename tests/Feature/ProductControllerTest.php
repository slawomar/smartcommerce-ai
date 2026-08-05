<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_opening_products(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_sees_only_their_own_products(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownProduct = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Produkt użytkownika',
                'sku' => 'OWN-001',
            ]);

        Product::factory()
            ->for($otherUser)
            ->create([
                'name' => 'Produkt innego użytkownika',
                'sku' => 'FOREIGN-001',
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('products.index'));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('products/Index')
                    ->where('products.total', 1)
                    ->has('products.data', 1)
                    ->where('products.data.0.id', $ownProduct->id)
                    ->where(
                        'products.data.0.name',
                        'Produkt użytkownika',
                    ),
            );
    }

    public function test_user_can_search_products_by_name_sku_and_description(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $productMatchingName = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Lampa biurkowa',
                'sku' => 'PRODUCT-001',
                'description' => 'Oświetlenie do gabinetu.',
            ]);

        $productMatchingSku = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Oświetlenie sufitowe',
                'sku' => 'LAMP-002',
                'description' => 'Produkt do salonu.',
            ]);

        $productMatchingDescription = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Produkt dekoracyjny',
                'sku' => 'PRODUCT-003',
                'description' => 'Nowoczesna lampa stojąca.',
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Krzesło biurowe',
                'sku' => 'CHAIR-001',
                'description' => 'Ergonomiczne krzesło.',
            ]);

        Product::factory()
            ->for($otherUser)
            ->create([
                'name' => 'Lampa innego użytkownika',
                'sku' => 'LAMP-FOREIGN-001',
                'description' => 'Produkt nie może być widoczny.',
            ]);

        $expectedProductIds = [
            $productMatchingName->id,
            $productMatchingSku->id,
            $productMatchingDescription->id,
        ];

        $response = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'search' => 'lamp',
            ]));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('products/Index')
                    ->where('filters.search', 'lamp')
                    ->where('products.total', 3)
                    ->has('products.data', 3)
                    ->where(
                        'products.data',
                        function (Collection $products) use ($expectedProductIds): bool {
                            $actualProductIds = $products
                                ->pluck('id')
                                ->sort()
                                ->values()
                                ->all();

                            $expectedProductIds = collect($expectedProductIds)
                                ->sort()
                                ->values()
                                ->all();

                            return $actualProductIds === $expectedProductIds;
                        },
                    ),
            );
    }

    public function test_product_search_is_trimmed_and_returned_in_filters(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Lampa Emilia',
                'sku' => 'LAMP-001',
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Biurko drewniane',
                'sku' => 'DESK-001',
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'search' => '  Lampa  ',
            ]));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('products/Index')
                    ->where('filters.search', 'Lampa')
                    ->where('products.total', 1)
                    ->has('products.data', 1)
                    ->where('products.data.0.id', $product->id),
            );
    }

    public function test_search_query_is_preserved_in_pagination_urls(): void
    {
        $user = User::factory()->create();

        Product::factory()
            ->count(11)
            ->for($user)
            ->create([
                'name' => 'Lampa testowa',
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'search' => 'lamp',
            ]));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('products/Index')
                    ->where('products.current_page', 1)
                    ->where('products.total', 11)
                    ->has('products.data', 10)
                    ->where(
                        'products.next_page_url',
                        fn (?string $url): bool => $url !== null
                            && str_contains($url, 'search=lamp'),
                    ),
            );
    }

    public function test_user_can_create_product(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('products.store'), [
                'name' => 'Laptop testowy',
                'sku' => ' laptop-001 ',
                'description' => 'Produkt utworzony w teście PHPUnit.',
                'price' => '2999.99',
                'stock' => 5,
                'is_active' => true,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'user_id' => $user->id,
            'name' => 'Laptop testowy',
            'sku' => 'LAPTOP-001',
            'description' => 'Produkt utworzony w teście PHPUnit.',
            'price' => 2999.99,
            'stock' => 5,
            'is_active' => 1,
        ]);
    }

    public function test_user_cannot_create_product_with_duplicate_sku(): void
    {
        $user = User::factory()->create();

        Product::factory()
            ->for($user)
            ->create([
                'sku' => 'LAPTOP-001',
            ]);

        $response = $this
            ->actingAs($user)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'name' => 'Drugi laptop',
                'sku' => ' laptop-001 ',
                'description' => null,
                'price' => '1999.99',
                'stock' => 3,
                'is_active' => true,
            ]);

        $response
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors([
                'sku',
            ]);

        $this->assertDatabaseCount('products', 1);

        $this->assertDatabaseMissing('products', [
            'user_id' => $user->id,
            'name' => 'Drugi laptop',
        ]);
    }

    public function test_different_users_can_use_the_same_sku(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        Product::factory()
            ->for($firstUser)
            ->create([
                'name' => 'Laptop pierwszego użytkownika',
                'sku' => 'LAPTOP-001',
            ]);

        $response = $this
            ->actingAs($secondUser)
            ->post(route('products.store'), [
                'name' => 'Laptop drugiego użytkownika',
                'sku' => ' laptop-001 ',
                'description' => null,
                'price' => '2499.99',
                'stock' => 7,
                'is_active' => true,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseCount('products', 2);

        $this->assertDatabaseHas('products', [
            'user_id' => $firstUser->id,
            'sku' => 'LAPTOP-001',
        ]);

        $this->assertDatabaseHas('products', [
            'user_id' => $secondUser->id,
            'name' => 'Laptop drugiego użytkownika',
            'sku' => 'LAPTOP-001',
        ]);
    }

    public function test_user_can_update_their_own_product(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Laptop przed edycją',
                'sku' => 'LAPTOP-001',
                'price' => '2499.99',
                'stock' => 5,
                'is_active' => true,
            ]);

        $response = $this
            ->actingAs($user)
            ->put(route('products.update', $product), [
                'name' => 'Laptop po edycji',
                'sku' => ' laptop-001 ',
                'description' => 'Opis produktu po aktualizacji.',
                'price' => '2999.99',
                'stock' => 12,
                'is_active' => false,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'user_id' => $user->id,
            'name' => 'Laptop po edycji',
            'sku' => 'LAPTOP-001',
            'description' => 'Opis produktu po aktualizacji.',
            'stock' => 12,
            'is_active' => 0,
        ]);
    }

    public function test_user_cannot_update_another_users_product(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = Product::factory()
            ->for($owner)
            ->create([
                'name' => 'Produkt właściciela',
                'sku' => 'OWNER-001',
                'price' => '1499.99',
                'stock' => 5,
                'is_active' => true,
            ]);

        $response = $this
            ->actingAs($otherUser)
            ->put(route('products.update', $product), [
                'name' => 'Nieautoryzowana zmiana',
                'sku' => 'HACKED-001',
                'description' => 'Próba zmiany cudzego produktu.',
                'price' => '1.00',
                'stock' => 999,
                'is_active' => false,
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'user_id' => $owner->id,
            'name' => 'Produkt właściciela',
            'sku' => 'OWNER-001',
            'stock' => 5,
            'is_active' => 1,
        ]);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'name' => 'Nieautoryzowana zmiana',
            'sku' => 'HACKED-001',
        ]);
    }

    public function test_user_can_delete_their_own_product(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Produkt do usunięcia',
                'sku' => 'DELETE-001',
            ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'user_id' => $user->id,
            'sku' => 'DELETE-001',
        ]);
    }

    public function test_user_cannot_delete_another_users_product(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = Product::factory()
            ->for($owner)
            ->create([
                'name' => 'Produkt właściciela',
                'sku' => 'OWNER-DELETE-001',
            ]);

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('products.destroy', $product));

        $response->assertForbidden();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'user_id' => $owner->id,
            'name' => 'Produkt właściciela',
            'sku' => 'OWNER-DELETE-001',
        ]);
    }
}
