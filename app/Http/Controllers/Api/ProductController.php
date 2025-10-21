<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * [GET /api/products]
     * Lists all products.
     */
    public function index()
    {
        // 1. Get all products from the database
        $products = Product::with('user')->get();

        // 2. build response
        $responseData = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price_gbp' => number_format($product->price / 100, 2, '.', ''),
                'created_at' => $product->created_at,
                // Get the seller's public info
                'seller' => [
                    'id' => $product->user->id,
                    'name' => $product->user->name
                ],
            ];
        });

        // 3. Return the data as JSON
        return response()->json($responseData);
    }

    /**
     * [POST /api/products]
     * Creates a new product.
     */
    public function store(Request $request)
    {
        // 1. Check if a user is logged in
        if (!$request->user()) {
            return response()->json(['message' => 'You must be logged in'], 401); //401 Unauthorised
        }

        // 2. Validate the data from the form
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0.01',
        ]);

        // 3. Create a new Product object
        $product = new Product();
        $product->name = $validated['name'];
        $product->description = $validated['description'];
        $product->price = (int) round($validated['price'] * 100); // Store as pence
        $product->user_id = $request->user()->id; // Get the logged-in user's ID

        // 4. Save it to the database
        $product->save();

        $product->load('user');

        // 5. Return the new product as JSON
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price_gbp' => number_format($product->price / 100, 2, '.', ''),
            'created_at' => $product->created_at,
            'seller' => [
                'id' => $product->user->id,
                'name' => $product->user->name,
            ],
        ]);
    }

    /**
     * [GET /api/products/:id]
     * Shows a single product.
     */
    public function show(Product $product)
    {
        // 1. $product from the ID in the URL
        $product->load('user');

        // 2. Return that product as JSON
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price_gbp' => number_format($product->price / 100, 2, '.', ''),
            'created_at' => $product->created_at,
            'seller' => [
                'id' => $product->user->id,
                'name' => $product->user->name,
            ],
        ]);
    }

    /**
     * [PUT /api/products/:id]
     * Edits an existing product.
     */
    public function update(Request $request, Product $product)
    {
        // 1. Check if a user is logged in
        if (!$request->user()) {
            return response()->json(['message' => 'You must be logged in'], 401); //401 Unauthorised
        }

        // 2. Check if the logged-in user is the one who created the product
        // This satisfies "editable... only by the user who created the product"
        if ($product->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You do not own this product'], 403); // 403 Forbidden
        }

        // 3. Validate the data
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0.01',
        ]);

        // 4. Update the product fields if they were sent in the request
        if (isset($validated['name'])) {
            $product->name = $validated['name'];
        }
        if (isset($validated['description'])) {
            $product->description = $validated['description'];
        }
        if (isset($validated['price'])) {
            $product->price = (int) round($validated['price'] * 100); // Store as pence
        }

        // 5. Save the changes
        $product->save();

        $product->load('user');

        // 6. Return the updated product (manually building the array)
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price_gbp' => number_format($product->price / 100, 2, '.', ''),
            'created_at' => $product->created_at,
            'seller' => [
                'id' => $product->user->id,
                'name' => $product->user->name,
            ],
        ]);
    }

    /**
     * [DELETE /api/products/:id]
     * Deletes an existing product.
     */
    public function destroy(Request $request, Product $product) // MODIFIED: Added Request $request
    {
        // 1. Check if a user is logged in
        if (!$request->user()) {
            return response()->json(['message' => 'You must be logged in'], 401); //401 Unauthorised
        }

        // 2. Check if the logged-in user is the one who created the product
        // This satisfies "deletable only by the user who created the product"
        if ($product->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You do not own this product'], 403); // 403 Forbidden
        }

        // 3. Delete the product
        // Because you added `SoftDeletes` to the model, this will soft delete it
        $product->delete();

        // 4. Return a success message
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
