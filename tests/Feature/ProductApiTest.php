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
        ->assertJsonCount(3, 'data') // Check that we got 3 products back
        ->assertJsonStructure([ // Check that the JSON structure is correct
            // The wildcard '*' checks each item in the array
            'data' => ['*' => ['id', 'name', 'price_gbp', 'seller' => ['id', 'name']]]
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
            ->assertJsonMissingPath('data.0.seller.email');
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

        $response->assertStatus(201) // 201 Created
            ->assertJsonPath('data.name', 'My New Product')
            ->assertJsonPath('data.seller.id', $user->id);

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
            ->assertJsonPath('data.name', 'Updated Name');
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

        // Your controller returns 204 No Content on successful deletion
        $response->assertStatus(204); // 204 No Content

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

    // Test for: Validation errors when creating a product with missing name field
    public function test_cannot_create_product_without_name(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson('/api/products', [
                // 'name' => 'Missing Name', // Omitted
                'description' => 'A valid description.',
                'price' => 19.99,
            ])
            ->assertStatus(422) // Expect Validation Error
            ->assertJsonValidationErrors(['name']); // Check 'name' field caused the error
    }

    // Test for: Validation errors when creating a product with missing description field
    public function test_cannot_create_product_without_description(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson('/api/products', [
                'name' => 'Valid Name',
                // 'description' => 'Missing description.', // Omitted
                'price' => 19.99,
            ])
            ->assertStatus(422) // Expect Validation Error
            ->assertJsonValidationErrors(['description']); // Check 'description' field caused the error
    }

    // Test for: Validation errors when creating a product with missing price field
    public function test_cannot_create_product_without_price(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson('/api/products', [
                'name' => 'Valid Name',
                'description' => 'A valid description.',
                // 'price' => 19.99, // Omitted
            ])
            ->assertStatus(422) // Expect Validation Error
            ->assertJsonValidationErrors(['price']); // Check 'price' field caused the error
    }

    // Test for: Validation errors when creating a product with invalid price type
    public function test_cannot_create_product_with_invalid_price(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson('/api/products', [
                'name' => 'Valid Name',
                'description' => 'A valid description.',
                'price' => 'not-a-number', // Invalid price
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['price']); // Check 'price' field caused the error

    }

    // Test for: Validation errors when creating a product with price less than minimum allowed
    public function test_cannot_create_product_with_too_low_price(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson('/api/products', [
                'name' => 'Valid Name',
                'description' => 'A valid description.',
                'price' => 0.00, // Price too low
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['price']); // Check 'price' field caused the error
    }

    // Test for: Show endpoint returns 404 for invalid product ID
    public function test_show_returns_404_for_invalid_id(): void
    {
        $this->getJson('/api/products/9999') // ID that won't exist
        ->assertStatus(404);
    }

    // Test for: Soft-deleted products are not listed in the product listing
    public function test_soft_deleted_product_is_not_listed(): void
    {
        $product = Product::factory()->create();
        $product->delete(); // Soft delete it

        $this->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data'); // Assuming no other products exist
    }

    // Test for: Validation errors when updating a product with invalid price type
    public function test_cannot_update_product_with_invalid_price(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->putJson('/api/products/' . $product->id, [
                'price' => 'this-is-not-a-number' // Send invalid data
            ])
            ->assertStatus(422) // Expect Validation Error
            ->assertJsonValidationErrors(['price']);
    }

    // Test for: Update endpoint returns 404 for invalid product ID
    public function test_update_returns_404_for_invalid_id(): void
    {
        $user = User::factory()->create(); // Need to be auth'd

        $this->actingAs($user)
            ->putJson('/api/products/9999', ['name' => 'test'])
            ->assertStatus(404);
    }

    // Test for: Delete endpoint returns 404 for invalid product ID
    public function test_delete_returns_404_for_invalid_id(): void
    {
        $user = User::factory()->create(); // Need to be auth'd

        $this->actingAs($user)
            ->deleteJson('/api/products/9999')
            ->assertStatus(404);
    }

    // Test for: Shows a single product with the given ID
    public function test_public_can_show_a_single_product(): void
    {
        // 1. Create a product
        $product = Product::factory()->create();

        // 2. Call the API endpoint for that specific product
        $response = $this->getJson('/api/products/' . $product->id);

        // 3. Check the results
        $response->assertStatus(200)
            ->assertJsonStructure([ // Check structure for a *single* item
                'data' => ['id', 'name', 'price_gbp', 'seller' => ['id', 'name']]
            ])
            ->assertJsonPath('data.name', $product->name) // Check if it's the right product
            ->assertJsonPath('data.seller.id', $product->user->id);
    }
}
