<?php

namespace Database\Factories;

use App\Domain\DataSource\Models\DataSource;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExchangeRate> */
class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'data_source_id' => DataSource::factory(),
            'base_currency' => ExchangeRate::BASE_CURRENCY,
            'quote_currency' => ExchangeRate::QUOTE_CURRENCY,
            'rate_type' => ExchangeRate::RATE_TYPE,
            'effective_date' => now('Asia/Phnom_Penh')->toDateString(),
            'rate' => '4000.125',
            'unit' => 1,
            'raw_hash' => hash('sha256', 'exchange-rate test data'),
        ];
    }
}
