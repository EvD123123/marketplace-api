<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\CurrencyService;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get requested currency from query string, default to GBP
        $requestedCurrency = $request->input('currency', 'GBP');

        // Get the product's stored currency (default GBP for legacy products)
        $storedCurrency = $this->currency ?? 'GBP';

        // Convert price using CurrencyService
        $currencyService = app(CurrencyService::class);
        $convertedPrice = $currencyService->convert(
            $this->price,
            $storedCurrency,
            $requestedCurrency
        );

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => number_format($convertedPrice / 100, 2, '.', ''),
            'currency' => $requestedCurrency,
            'original_currency' => $storedCurrency,
            'created_at' => $this->created_at->format('d/m/Y H:i:s'),
            'seller' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
