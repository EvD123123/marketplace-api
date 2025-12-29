<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    /**
     * Convert an amount from one currency to another.
     *
     * @param int $priceInPence
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float
     */
    public function convert(int $priceInPence, string $fromCurrency, string $toCurrency): float
    {
        // No conversion needed if currencies match
        if ($fromCurrency === $toCurrency) {
            return $priceInPence;
        }

        $rates = $this->getRates();

        // Safety check: if API fails, return original amount
        if (empty($rates)) {
            return $priceInPence;
        }

        // Logic: (Amount / Rate_From) * Rate_To
        // We use "fallback to 1" (?? 1) to prevent division by zero errors
        $rateFrom = $rates[$fromCurrency] ?? 1;
        $rateTo = $rates[$toCurrency] ?? 1;

        return ($priceInPence / $rateFrom) * $rateTo;
    }

    /**
     * Fetch exchange rates with caching to respect strict API limits.
     *
     * @return array
     */
    protected function getRates(): array
    {
        // Cache for 24 hours to stay under 100 requests/month limit.
        return Cache::remember('exchange_rates', 60 * 24, function () {
            $apiKey = env('EXCHANGE_RATES_API_KEY');

            $response = Http::get('http://api.exchangeratesapi.io/v1/latest', [
                'access_key' => $apiKey,
                'symbols' => 'GBP,USD,EUR',
            ]);

            if ($response->successful()) {
                return $response->json()['rates'] ?? [];
            }

            return [];
        });
    }
}
