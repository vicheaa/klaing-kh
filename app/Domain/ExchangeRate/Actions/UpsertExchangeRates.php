<?php

namespace App\Domain\ExchangeRate\Actions;

use App\Domain\DataSource\Models\DataSource;
use App\Domain\ExchangeRate\Data\ExchangeRateData;
use App\Domain\ExchangeRate\Models\ExchangeRate;

class UpsertExchangeRates
{
    /** @param non-empty-array<array-key, ExchangeRateData> $rates */
    public function handle(DataSource $source, array $rates, string $rateType): void
    {
        $rows = array_values(array_map(
            fn (ExchangeRateData $rate): array => [
                'data_source_id' => $source->id,
                'base_currency' => $rate->baseCurrency,
                'quote_currency' => ExchangeRate::QUOTE_CURRENCY,
                'rate_type' => $rateType,
                'effective_date' => $rate->effectiveDate,
                'rate' => $rate->averageRate,
                'unit' => $rate->unit,
                'source_recorded_at' => $rate->sourceRecordedAt?->toDateTimeString(),
                'raw_hash' => $rate->rawHash,
            ],
            $rates,
        ));

        ExchangeRate::query()->upsert(
            $rows,
            uniqueBy: ['data_source_id', 'base_currency', 'quote_currency', 'rate_type', 'effective_date'],
            update: ['rate', 'unit', 'source_recorded_at', 'raw_hash'],
        );
    }
}
