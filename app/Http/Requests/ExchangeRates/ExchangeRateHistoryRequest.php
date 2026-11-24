<?php

namespace App\Http\Requests\ExchangeRates;

use Illuminate\Foundation\Http\FormRequest;

class ExchangeRateHistoryRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => $this->filled('from')
                ? ['nullable', 'date_format:Y-m-d', 'after_or_equal:from']
                : ['nullable', 'date_format:Y-m-d'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'lang' => ['sometimes', 'in:en,km'],
        ];
    }

    /** @return array{from?: ?string, to?: ?string, page?: int|numeric-string, per_page?: int|numeric-string, lang?: 'en'|'km'} */
    public function filters(): array
    {
        return $this->validated();
    }
}
