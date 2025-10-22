<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    use SoftDeletes;

    // Attributes that are mass assignable.
    protected $fillable = ['name', 'description', 'price', 'user_id'];

    // Get the product's price automaticaly convert to pence when setting
    protected function price(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => (int) round($value * 100),
        );
    }

    // Get the product's price automaticaly converted to pounds when getting
    protected function priceGbp(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->price / 100, 2, '.', ''),
        );
    }

    // Inverse relationship
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
