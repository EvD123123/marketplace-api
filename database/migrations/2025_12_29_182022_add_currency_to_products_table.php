<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // "For existing products in the database, GBP should be set as their currency."
            // "If no currency parameter is supplied, or if it’s empty, the default currency should be GBP."
            $table->string('currency', 3)->default('GBP')->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
