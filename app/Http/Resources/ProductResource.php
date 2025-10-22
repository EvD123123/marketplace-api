<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price_gbp' => $this->price_gbp, // Uses the accessor automatically
            'created_at' => $this->created_at->format('d/m/Y H:i:s'),
            // Include the seller info
            'seller' => new UserResource($this->whenLoaded('user')), // 'whenLoaded' prevents an error if the 'user' relationship wasn't fetched
            ];
    }
}
