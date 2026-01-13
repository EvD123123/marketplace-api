<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * [GET /api/products]
     * Lists all products with pagination and optional search.
     */
    public function index(Request $request)
    {
        // 1. Start the query (don't get() yet)
        $query = Product::with('user');

        // 2. SEARCH: If the URL has ?search=something
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            // "We're only interested on searching by name (partial matches)"
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        // 3. PAGINATION: "15 records should be returned per page"
        $products = $query->paginate(15);

        // 4. Ensure query params (like search=shirt) stay in the "Next Page" links
        $products->appends($request->query());

        // 5. Return the result
        return ProductResource::collection($products);
    }

    /**
     * [POST /api/products]
     * Creates a new product.
     */
    public function store(StoreProductRequest $request)
    {
        // 1. Validation & Auth is handled by StoreProductRequest
        // 2. Create the product using the validated data
        $product = $request->user()->products()->create($request->validated());

        // 3. Return the new product, formatted by the Resource
        // We eager load 'user' to ensure it's included
        return new ProductResource($product->load('user'));
    }

    /**
     * [GET /api/products/:id]
     * Shows a single product.
     */
    public function show(Product $product)
    {
        // 1. Route-model binding finds the product
        // 2. Return the product, formatted by the Resource
        return new ProductResource($product->load('user'));
    }

    /**
     * [PUT /api/products/:id]
     * Edits an existing product.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        // 1. Validation & Auth is handled by UpdateProductRequest
        // 2. Update the product with validated data
        $product->update($request->validated());

        // 3. Return the updated product, formatted by the Resource
        return new ProductResource($product->load('user'));
    }

    /**
     * [DELETE /api/products/:id]
     * Deletes an existing product.
     */
    public function destroy(Product $product)
    {
        // 1. Authorise using the ProductPolicy
        //    This automatically checks the 'delete' method
        $this->authorize('delete', $product);

        // 2. Delete the product
        $product->delete();

        // 3. Return a "204 No Content" response (standard for DELETE)
        return response()->noContent();
    }
}


