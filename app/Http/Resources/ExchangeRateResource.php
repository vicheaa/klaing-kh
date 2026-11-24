<?php

namespace App\Http\Resources;

use App\Domain\ExchangeRate\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ExchangeRate */
class ExchangeRateResource extends JsonResource
{
    /** @return array{base_currency: string, quote_currency: string, rate_type: string, rate: string, unit: int, effective_date: string} */
    public function toArray(Request $request): array
    {
        return [
            'base_currency' => $this->base_currency,
            'quote_currency' => $this->quote_currency,
            'rate_type' => $this->rate_type,
            'rate' => $this->rate,
            'unit' => $this->unit,
            'effective_date' => $this->effective_date,
        ];
    }
}
