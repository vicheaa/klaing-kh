<?php

namespace App\Domain\ExchangeRate\Models;

use Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(ExchangeRateFactory::class)]
class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory;

    public const string BASE_CURRENCY = 'USD';

    public const string QUOTE_CURRENCY = 'KHR';

    public const string RATE_TYPE = 'average';

    public const string OFFICIAL_RATE_TYPE = 'official';

    /** @return array{source_recorded_at: 'immutable_datetime', rate: 'decimal:10'} */
    protected function casts(): array
    {
        return [
            'source_recorded_at' => 'immutable_datetime',
            'rate' => 'decimal:10',
        ];
    }
}
