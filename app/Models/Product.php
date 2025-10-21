<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    use SoftDeletes;

    // Attributes that are mass assignable.
    protected $fillable = ['name', 'description', 'price', 'user_id'];

    // Inverse relationship
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
