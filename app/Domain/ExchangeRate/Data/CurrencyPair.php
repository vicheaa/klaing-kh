<?php

namespace App\Domain\ExchangeRate\Data;

use App\Domain\ExchangeRate\Models\ExchangeRate;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class CurrencyPair
{
    public function __construct(
        public string $base = ExchangeRate::BASE_CURRENCY,
        public string $quote = ExchangeRate::QUOTE_CURRENCY,
    ) {}

    public function includesRiel(): bool
    {
        return $this->base === ExchangeRate::QUOTE_CURRENCY || $this->quote === ExchangeRate::QUOTE_CURRENCY;
    }

    public function foreignCurrency(): string
    {
        return $this->base === ExchangeRate::QUOTE_CURRENCY ? $this->quote : $this->base;
    }

    public function rate(ExchangeRate $record): string
    {
        if ($this->quote === ExchangeRate::QUOTE_CURRENCY && $record->unit === 1) {
            return $record->rate;
        }

        if ($this->quote === ExchangeRate::QUOTE_CURRENCY) {
            $numerator = BigDecimal::of($record->rate);
            $denominator = $record->unit;
        } elseif ($this->base === ExchangeRate::QUOTE_CURRENCY) {
            $numerator = BigDecimal::of($record->unit);
            $denominator = $record->rate;
        } else {
            $numerator = BigDecimal::of($record->rate)->multipliedBy((int) $record->getAttribute('target_unit'));
            $denominator = BigDecimal::of((string) $record->getAttribute('target_rate'))->multipliedBy($record->unit);
        }

        return (string) $numerator->dividedBy($denominator, 24, RoundingMode::HalfUp)->strippedOfTrailingZeros();
    }
}
