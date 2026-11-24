<?php

namespace App\Http\Requests\ExchangeRates;

use App\Domain\ExchangeRate\Data\CurrencyPair;
use App\Domain\ExchangeRate\Models\ExchangeRate;

class CurrencyPairHistoryRequest extends ExchangeRateHistoryRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'base' => ['sometimes', 'string', 'regex:/^[A-Z]{3}$/D'],
            'quote' => ['sometimes', 'string', 'regex:/^[A-Z]{3}$/D'],
        ];
    }

    public function pair(): CurrencyPair
    {
        return new CurrencyPair(
            base: $this->validated('base', ExchangeRate::BASE_CURRENCY),
            quote: $this->validated('quote', ExchangeRate::QUOTE_CURRENCY),
        );
    }
}
