# Marketplace API - Project Setup

This document outlines the initial setup for the Marketplace API project, covering project installation, database configuration, model creation, API authentication, endpoint creation, and automated testing as per Part 1 of the task.
## Step 1: Project Setup & Database Configuration

1.  **Install Laravel**:
    * Installed a new Laravel 11 project named `marketplace-api` using Composer.
    * `composer create-project laravel/laravel marketplace-api`

2.  **Initialise Git**:
    * Navigated into the new project directory.
    * Initialised a new Git repository.
    * Staged all files (`git add .`) and made the first commit.
    * `git commit -m "Initial Laravel project setup"`

3.  **Configure Database (SQLite)**:
    * As requested, the project was configured to use SQLite.
    * Edited the `.env` file.
    * Changed `DB_CONNECTION` to `sqlite`.
    * Removed the unused `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` lines.
    * Created an empty database file: `touch database/database.sqlite`.
    * Set the `DB_DATABASE` variable in `.env` to the full absolute path of the new sqlite file.
    * Ran the initial migrations to create the default `users` table.
    * `php artisan migrate`

## Step 2: Create Models & Migrations

This step defines the data structure for `Users` and `Products`.

1.  **User Model**:
    * The default `User` model provided by Laravel was used.

2.  **Product Model & Migration**:
    * Generated a new `Product` model using the command:
    * `php artisan make:model Product -mfs`
    * This command also generated:
        * A migration file (`-m`)
        * A factory (`-f`)
        * A seeder (`-s`)

3.  **Edit Product Migration**:
    * Opened the `database/migrations/..._create_products_table.php` file.
    * In the `up` method, the schema was defined to include:
        * `id()`
        * `foreignId('user_id')` linked to the `users` table with a cascading delete.
        * `string('name')`
        * `text('description')`
        * `integer('price')` (to store the value in pence)
        * `timestamps()`
        * `softDeletes()` (to add the `deleted_at` column)

4.  **Edit Models**:
    * **Product Model** (`app/Models/Product.php`):
        * Imported and used the `SoftDeletes` trait.
        * Defined the `$fillable` array to allow mass assignment for `name`, `description`, `price`, and `user_id`.
        * Defined the inverse `user()` relationship using `BelongsTo`.
    * **User Model** (`app/Models/User.php`):
        * Imported `HasMany`.
        * Defined the `products()` relationship using `HasMany`.

5.  **Run Migrations**:
    * Ran the migrations to create the new `products` table in the database.
    * `php artisan migrate`

## Step 3: Set Up Authentication

Set up API authentication using Laravel Sanctum, as non-users cannot create products.

1.  **Install Sanctum**:
    * `composer require laravel/sanctum`

2.  **Publish Configuration**:
    * `php artisan vendor:publish --provider="Laravel\Sancom\SanctumServiceProvider"`

3.  **Run Migration**:
    * Ran the migrations, which added a table for API tokens required by Sanctum.
    * `php artisan migrate`

4.  **Configure Middleware**:
    * In `bootstrap/app.php`, located the `withMiddleware` section.
    * Added Sanctum's middleware to the `api` group.

## Step 4: Build the API Endpoints (Routes & Controller)

This section creates the controller to handle logic and the routes to point URLs to that logic.

1.  **Create Controller**:
    * Generated an API resource controller:
    * `php artisan make:controller Api/ProductController --api`
    * This command stubs out a controller with the standard methods: `index`, `store`, `show`, `update`, and `destroy`.

2.  **Define Routes**:
    * Opened `routes/api.php` to define the application's API routes.
    * **Public Routes**:
        * `Route::get('/products', [ProductController::class, 'index']);`
        * `Route::get('/products/{product}', [ProductController::class, 'show']);`
    * **Protected Routes**:
        * A route group was created using the `auth:sanctum` middleware to protect endpoints.
        * `Route::post('/products', [ProductController::class, 'store']);`
        * `Route::put('/products/{product}', [ProductController::class, 'update']);`
        * `Route::delete('/products/{product}', [ProductController::class, 'destroy']);`

## Step 5: Implement Business Logic in Controller

This step involves implementing validation, authorisation, and data formatting directly within the `Api/ProductController`.

1.  **Fill in ProductController Logic**:
    * Opened `Api/ProductController` and implemented all logic for the five endpoints.

2.  **Validation (In Controller)**:
    * In the `store` and `update` methods, validation was handled directly using `$request->validate()`.
    * Rules were defined for `name`, `description`, and `price`.

3.  **Authorisation & Authentication (In Controller)**:
    * In the `store`, `update`, and `destroy` methods, authentication was checked manually using `if (!$request->user())` to return a `401 Unauthorised` response.
    * In the `update` and `destroy` methods, authorisation (ensuring only the owner can edit/delete) was checked manually by comparing the product's `user_id` to the authenticated user's `id`. This returns a `403 Forbidden` response if the IDs do not match.

4.  **Data Formatting & Response (In Controller)**:
    * In the `index`, `store`, `show`, and `update` methods, the JSON response was built manually by creating an array.
    * This array includes the product details, the `price` formatted as `price_gbp`, and the public `seller` information (ID and name).
    * The `user` relationship was eager-loaded using `::with('user')` or `->load('user')` to prevent N+1 query problems.
    * The `destroy` method returns a JSON success message and a `204 No Content` status.

## Step 6: Write Automated Tests

Wrote automated tests to prove that all the logic built in the controller works correctly and meets all the requirements.

1.  **Configure Test Database**:
    * I opened the `phpunit.xml` file and configured it to use a fast, in-memory SQLite database for testing. This keeps the real database clean.
    * I added these two lines to the `<php>` section:
        ```xml
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        ```

2.  **Create Test File**:
    * Ran the following command to create the test file:
        ```bash
        php artisan make:test ProductApiTest
        ```
    * This created the new file at `tests/Feature/ProductApiTest.php`.

3.  **Add Test Code**:
    * Opened `tests/Feature/ProductApiTest.php` and replaced its entire contents with a test suite designed to work with the specific "manual" controller I wrote.
    * The tests use the `RefreshDatabase` trait and `Sanctum::actingAs()` to simulate API logins.
    * The test suite covers all required cases: listing products, checking for sensitive data, guest/user creation, owner/non-owner updates, and owner/non-owner deletes (including checking for soft deletes).

4.  **Run Tests**:
    * I ran all the tests from the terminal:
        ```bash
        php artisan test
        ```
    * All tests passed, confirming the API is working as required.

## Step 7: Refactoring & Additional Tests

After the initial implementation in the project was refactored to improve maintainability, code quality, and adherence to standard principles. The controller was slimmed down by delegating responsibilities to dedicated classes, as is standard in modern Laravel development.

1.  **Refactor Controller Logic**:
    * **Authorisation**: All manual authentication (`Auth::check()`) and authorisation (`$product->user_id === $user->id`) checks were removed from the `ProductController`.
    * **Validation**: All `$request->validate()` calls were removed from the controller.
    * **Response Formatting**: All manual JSON response building (e.g., formatting `price_gbp`, creating the `seller` array) was removed from the controller.

2.  **Implement Form Requests**:
    * Changes to `StoreProductRequest` and `UpdateProductRequest`.
    * These classes now automatically handle all **validation** and **authorisation** logic for the `store` and `update` methods.

3.  **Implement API Resources**:
    * Utilised the existing `ProductResource` and `UserResource` to manage all outgoing JSON responses.
    * This centralises response formatting, ensuring a consistent API output and removing repetition.
    * The `ProductResource` now handles the transformation of `price` to `price_gbp`.

4.  **Update Base Controller**:
    * Modified `app/Http/Controllers/Controller.php` to use the `AuthorisesRequests` trait.
    * This enabled the use of the `$this->authorise()` method in the `destroy` action for policy-based authorisation.

5.  **Update & Expand Test Suite**:
    * The existing test suite (`ProductApiTest.php`) was updated to work with the refactored code.
    * Assertions were modified to expect correct status codes (e.g., `201 Created` on store, `204 No Content` on delete).
    * Assertions were updated to check for the new JSON structure returned by API Resources (e.g., `data` wrapper).
    * Additional tests were added to cover edge cases and ensure full coverage of the refactored code to keep downtime for our uses to a minimum.

