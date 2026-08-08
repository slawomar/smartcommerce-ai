<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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
                'image_path' => 'products/own-product.jpg',
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
                    )
                    ->where(
                        'products.data.0.image_path',
                        'products/own-product.jpg',
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

    public function test_active_filters_are_preserved_in_pagination_urls(): void
    {
        $user = User::factory()->create();

        Product::factory()
            ->count(11)
            ->for($user)
            ->create([
                'name' => 'Lampa testowa',
                'stock' => 3,
                'is_active' => true,
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'search' => 'lamp',
                'status' => 'active',
                'stock' => 'low',
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
                            && str_contains($url, 'search=lamp')
                            && str_contains($url, 'status=active')
                            && str_contains($url, 'stock=low'),
                    ),
            );
    }

    public function test_user_can_filter_products_by_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $activeProduct = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Produkt aktywny',
                'is_active' => true,
            ]);

        $inactiveProduct = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Produkt nieaktywny',
                'is_active' => false,
            ]);

        Product::factory()
            ->for($otherUser)
            ->create([
                'name' => 'Aktywny produkt innego użytkownika',
                'is_active' => true,
            ]);

        $activeResponse = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'status' => 'active',
            ]));

        $activeResponse
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('products/Index')
                    ->where('filters.status', 'active')
                    ->where('products.total', 1)
                    ->has('products.data', 1)
                    ->where('products.data.0.id', $activeProduct->id),
            );

        $inactiveResponse = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'status' => 'inactive',
            ]));

        $inactiveResponse
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('products/Index')
                    ->where('filters.status', 'inactive')
                    ->where('products.total', 1)
                    ->has('products.data', 1)
                    ->where('products.data.0.id', $inactiveProduct->id),
            );
    }

    public function test_user_can_filter_products_by_stock_level(): void
    {
        $user = User::factory()->create();

        $availableProduct = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Produkt dostępny',
                'stock' => 20,
            ]);

        $lowStockProduct = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Produkt z niskim stanem',
                'stock' => 5,
            ]);

        $outOfStockProduct = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Produkt niedostępny',
                'stock' => 0,
            ]);

        $availableResponse = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'stock' => 'available',
            ]));

        $availableResponse
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('filters.stock', 'available')
                    ->where('filterOptions.lowStockThreshold', 5)
                    ->where('products.total', 1)
                    ->where(
                        'products.data.0.id',
                        $availableProduct->id,
                    ),
            );

        $lowStockResponse = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'stock' => 'low',
            ]));

        $lowStockResponse
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('filters.stock', 'low')
                    ->where('products.total', 1)
                    ->where(
                        'products.data.0.id',
                        $lowStockProduct->id,
                    ),
            );

        $outOfStockResponse = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'stock' => 'out',
            ]));

        $outOfStockResponse
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('filters.stock', 'out')
                    ->where('products.total', 1)
                    ->where(
                        'products.data.0.id',
                        $outOfStockProduct->id,
                    ),
            );
    }

    public function test_user_can_combine_search_status_and_stock_filters(): void
    {
        $user = User::factory()->create();

        $matchingProduct = Product::factory()
            ->for($user)
            ->create([
                'name' => 'Lampa magazynowa',
                'sku' => 'LAMP-001',
                'description' => 'Oświetlenie techniczne.',
                'stock' => 3,
                'is_active' => true,
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Lampa nieaktywna',
                'sku' => 'LAMP-002',
                'stock' => 3,
                'is_active' => false,
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Lampa z dużym stanem',
                'sku' => 'LAMP-003',
                'stock' => 20,
                'is_active' => true,
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Krzesło magazynowe',
                'sku' => 'CHAIR-001',
                'stock' => 3,
                'is_active' => true,
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'search' => 'lampa',
                'status' => 'active',
                'stock' => 'low',
            ]));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('products/Index')
                    ->where('filters.search', 'lampa')
                    ->where('filters.status', 'active')
                    ->where('filters.stock', 'low')
                    ->where('products.total', 1)
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.id',
                        $matchingProduct->id,
                    ),
            );
    }

    public function test_invalid_product_filters_are_ignored(): void
    {
        $user = User::factory()->create();

        Product::factory()
            ->count(2)
            ->for($user)
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'status' => 'unknown',
                'stock' => 'invalid',
            ]));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('products/Index')
                    ->where('filters.status', '')
                    ->where('filters.stock', '')
                    ->where('products.total', 2)
                    ->has('products.data', 2),
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

    public function test_user_can_sort_products_by_name(): void
    {
        $user = User::factory()->create();

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Zegar',
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Biurko',
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Lampa',
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'sort' => 'name',
                'direction' => 'asc',
            ]));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('products/Index')
                    ->where('filters.sort', 'name')
                    ->where('filters.direction', 'asc')
                    ->where('products.data.0.name', 'Biurko')
                    ->where('products.data.1.name', 'Lampa')
                    ->where('products.data.2.name', 'Zegar'),
            );
    }

    public function test_user_can_sort_products_by_price_descending(): void
    {
        $user = User::factory()->create();

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Produkt tani',
                'price' => 100,
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Produkt drogi',
                'price' => 500,
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Produkt średni',
                'price' => 250,
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'sort' => 'price',
                'direction' => 'desc',
            ]));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('filters.sort', 'price')
                    ->where('filters.direction', 'desc')
                    ->where('products.data.0.name', 'Produkt drogi')
                    ->where('products.data.1.name', 'Produkt średni')
                    ->where('products.data.2.name', 'Produkt tani'),
            );
    }

    public function test_invalid_sorting_values_fall_back_to_defaults(): void
    {
        $user = User::factory()->create();

        Product::factory()
            ->count(2)
            ->for($user)
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'sort' => 'user_id',
                'direction' => 'invalid',
            ]));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('products/Index')
                    ->where('filters.sort', 'created_at')
                    ->where('filters.direction', 'desc')
                    ->where('products.total', 2),
            );
    }

    public function test_sorting_can_be_combined_with_product_filters(): void
    {
        $user = User::factory()->create();

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Lampa tańsza',
                'sku' => 'LAMP-001',
                'price' => 100,
                'stock' => 3,
                'is_active' => true,
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Lampa droższa',
                'sku' => 'LAMP-002',
                'price' => 300,
                'stock' => 2,
                'is_active' => true,
            ]);

        Product::factory()
            ->for($user)
            ->create([
                'name' => 'Lampa nieaktywna',
                'sku' => 'LAMP-003',
                'price' => 500,
                'stock' => 2,
                'is_active' => false,
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('products.index', [
                'search' => 'lampa',
                'status' => 'active',
                'stock' => 'low',
                'sort' => 'price',
                'direction' => 'desc',
            ]));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('products.total', 2)
                    ->where('products.data.0.name', 'Lampa droższa')
                    ->where('products.data.1.name', 'Lampa tańsza')
                    ->where('filters.sort', 'price')
                    ->where('filters.direction', 'desc'),
            );
    }

    public function test_user_can_create_product_with_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $image = UploadedFile::fake()
            ->image('lamp.jpg', 1200, 800)
            ->size(1024);

        $response = $this
            ->actingAs($user)
            ->post(route('products.store'), [
                'name' => 'Lampa ze zdjęciem',
                'sku' => 'LAMP-IMAGE-001',
                'description' => 'Produkt ze zdjęciem.',
                'image' => $image,
                'price' => '499.99',
                'stock' => 5,
                'is_active' => true,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $product = Product::query()
            ->where('user_id', $user->id)
            ->where('sku', 'LAMP-IMAGE-001')
            ->firstOrFail();

        $this->assertNotNull($product->image_path);
        $this->assertStringStartsWith(
            'products/',
            $product->image_path,
        );

        Storage::disk('public')->assertExists(
            $product->image_path,
        );
    }

    public function test_product_image_must_be_a_supported_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->create(
            'document.pdf',
            100,
            'application/pdf',
        );

        $response = $this
            ->actingAs($user)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'name' => 'Produkt testowy',
                'sku' => 'IMAGE-VALIDATION-001',
                'description' => null,
                'image' => $file,
                'price' => '100.00',
                'stock' => 5,
                'is_active' => true,
            ]);

        $response
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('products', [
            'user_id' => $user->id,
            'sku' => 'IMAGE-VALIDATION-001',
        ]);
    }

    public function test_user_can_replace_product_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Storage::disk('public')->put(
            'products/old-image.jpg',
            'old image',
        );

        $product = Product::factory()
            ->for($user)
            ->create([
                'sku' => 'REPLACE-IMAGE-001',
                'image_path' => 'products/old-image.jpg',
            ]);

        $newImage = UploadedFile::fake()
            ->image('new-image.jpg', 1200, 800)
            ->size(1024);

        $response = $this
            ->actingAs($user)
            ->put(route('products.update', $product), [
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'image' => $newImage,
                'price' => $product->price,
                'stock' => $product->stock,
                'is_active' => $product->is_active,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertNotNull($product->image_path);
        $this->assertNotSame(
            'products/old-image.jpg',
            $product->image_path,
        );

        Storage::disk('public')->assertExists(
            $product->image_path,
        );

        Storage::disk('public')->assertMissing(
            'products/old-image.jpg',
        );
    }

    public function test_deleting_product_also_deletes_its_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Storage::disk('public')->put(
            'products/product-image.jpg',
            'image',
        );

        $product = Product::factory()
            ->for($user)
            ->create([
                'image_path' => 'products/product-image.jpg',
            ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);

        Storage::disk('public')->assertMissing(
            'products/product-image.jpg',
        );
    }

    public function test_user_can_remove_product_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Storage::disk('public')->put(
            'products/image-to-remove.jpg',
            'image',
        );

        $product = Product::factory()
            ->for($user)
            ->create([
                'image_path' => 'products/image-to-remove.jpg',
            ]);

        $response = $this
            ->actingAs($user)
            ->post(route('products.update', $product), [
                '_method' => 'put',
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'remove_image' => true,
                'price' => $product->price,
                'stock' => $product->stock,
                'is_active' => $product->is_active,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertNull($product->image_path);

        Storage::disk('public')->assertMissing(
            'products/image-to-remove.jpg',
        );
    }
}
