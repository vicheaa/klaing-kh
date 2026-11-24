<?php

namespace App\Domain\ExchangeRate\Data;

use Carbon\CarbonImmutable;

final readonly class ExchangeRateData
{
    public function __construct(
        public string $baseCurrency,
        public int $unit,
        public string $effectiveDate,
        public string $averageRate,
        public ?CarbonImmutable $sourceRecordedAt,
        public string $rawHash,
    ) {}
}
