<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    // This trait automatically resets your database after every single test
    use RefreshDatabase;

    // Test for: Details (inc. the seller's info) of every product is publicly available
    public function test_public_can_list_all_products_with_seller_info(): void
    {
        // Create 3 dummy products
        Product::factory(3)->create();

        // Call the API endpoint
        $response = $this->getJson('/api/products');

        // check the results
        $response->assertStatus(200) // Check for HTTP 200 OK
        ->assertJsonCount(3) // Check that we got 3 products back
        ->assertJsonStructure([ // Check that the JSON structure is correct
            // This flat structure '*' matches your manual controller's output
            '*' => ['id', 'name', 'price_gbp', 'seller' => ['id', 'name']]
        ]);
    }

    // Test for: You haven't publicly exposed any sensitive user data
    public function test_api_does_not_expose_sensitive_user_data(): void
    {
        Product::factory()->create();
        $response = $this->getJson('/api/products');

        // Check that the seller's email is NOT in the JSON
        // This path '0.seller.email' matches your manual controller's output
        $response->assertStatus(200)
            ->assertJsonMissingPath('0.seller.email');
    }

// Test for: A non-user shouldn't be able to create a product
    public function test_guest_cannot_create_a_product(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'New Product',
            'description' => 'Test desc',
            'price' => 10.50
        ]);

        $response->assertStatus(401); // 401 Unauthorised
    }

// Test for: A product listing can be created by a user
    public function test_authenticated_user_can_create_a_product(): void
    {
        $user = User::factory()->create();

        // <-- 2. USE SANCTUM::actingAs for API authentication
        $response = $this->actingAs($user)
            ->postJson('/api/products', [
                'name' => 'My New Product',
                'description' => 'A great product.',
                'price' => 19.99,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'My New Product')
            ->assertJsonPath('seller.id', $user->id);

        // check it was actually saved in the database
        $this->assertDatabaseHas('products', [
            'name' => 'My New Product',
            'user_id' => $user->id,
            'price' => 1999 // Check it's stored as pence
        ]);
    }

// Test for A product listing can be updated by a user who created it
    public function test_product_owner_can_update_their_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user) // Log in as the owner
        ->putJson('/api/products/' . $product->id, [
            'name' => 'Updated Name'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Updated Name');
    }

// Test for: Products should be editable only by the user who created the product
    public function test_user_cannot_update_another_users_product(): void
    {
        $owner = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $owner->id]);

        $nonOwner = User::factory()->create(); // A different user

        $response = $this->actingAs($nonOwner) // Log in as the non-owner
        ->putJson('/api/products/' . $product->id, [
            'name' => 'Updated Name'
        ]);

        $response->assertStatus(403); // 403 Forbidden
    }

// Test for: A product listing can be deleted by the user who created it
    public function test_product_owner_can_delete_their_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user) // Log in as the owner
        ->deleteJson('/api/products/' . $product->id);

        // Your controller returns 200 and a JSON message, so this is correct
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Product deleted successfully');

        // Test for: Deleted products are still visible in the database (only soft deleted)
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

// Test for: Products should be deletable only by the user who created the product
    public function test_user_cannot_delete_another_users_product(): void
    {
        $owner = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $owner->id]);

        $nonOwner = User::factory()->create();

        $response = $this->actingAs($nonOwner) // Log in as the non-owner
        ->deleteJson('/api/products/' . $product->id);

        $response->assertStatus(403); // 403 Forbidden

        // Make sure it wasn't deleted
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }
}
