<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

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
