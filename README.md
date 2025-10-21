# Marketplace API - Project Setup (Steps 1 & 2)

This document outlines the Marketplace API project, covering project installation, database configuration, and model/migration creation as per Part 1 of the task.

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
