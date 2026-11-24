<?php

namespace App\Integrations\DataEf;

use App\Domain\ExchangeRate\Data\ExchangeRateData;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use JsonException;

class NbcExchangeRateAdapter
{
    public function __construct(private DataEfClient $client) {}

    /** @return non-empty-array<string, ExchangeRateData> */
    public function fetch(): array
    {
        $body = $this->client->fetchExchangeRates();

        try {
            $payload = json_decode($body, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new DataEfException('invalid_json');
        }

        $data = is_array($payload) ? ($payload['data'] ?? null) : null;

        if (! is_array($data) || ! array_is_list($data) || count($data) < 2 || count($data) > 64) {
            throw new DataEfException('invalid_snapshot');
        }

        $rates = [];
        $effectiveDate = null;
        foreach ($data as $row) {
            $rate = $this->parseRate($row);
            $effectiveDate ??= $rate->effectiveDate;

            if ($rate->effectiveDate !== $effectiveDate || isset($rates[$rate->baseCurrency])) {
                throw new DataEfException('inconsistent_snapshot');
            }

            $rates[$rate->baseCurrency] = $rate;
        }

        if (! isset($rates[ExchangeRate::BASE_CURRENCY])) {
            throw new DataEfException('incomplete_snapshot');
        }

        return $rates;
    }

    private function parseRate(mixed $data): ExchangeRateData
    {
        if (! is_array($data)) {
            throw new DataEfException('invalid_rate_record');
        }

        $currency = $data['currency_id'] ?? null;
        $unit = $data['unit'] ?? null;

        if (! is_string($currency) || ! preg_match('/^[A-Z]{3}$/D', $currency)
            || $currency === ExchangeRate::QUOTE_CURRENCY
            || ($data['symbol'] ?? null) !== $currency.'/'.ExchangeRate::QUOTE_CURRENCY
            || ! is_int($unit) || $unit < 1 || $unit > 1000000) {
            throw new DataEfException('unexpected_currency_pair');
        }

        $effectiveDate = $data['valid_date'] ?? null;
        $recordedAt = $data['created_at'] ?? null;
        $average = $data['average'] ?? null;

        $effectiveDay = is_string($effectiveDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $effectiveDate)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $effectiveDate)
            : false;

        if ($effectiveDay === false || $effectiveDay->format('Y-m-d') !== $effectiveDate) {
            throw new DataEfException('invalid_effective_date');
        }

        if (! is_string($recordedAt) || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D', $recordedAt)) {
            throw new DataEfException('invalid_source_timestamp');
        }

        $timestampFormat = str_contains($recordedAt, '.') ? '!Y-m-d\TH:i:s.uP' : '!Y-m-d\TH:i:sP';
        $sourceRecordedAt = DateTimeImmutable::createFromFormat($timestampFormat, $recordedAt);

        if ($sourceRecordedAt === false || DateTimeImmutable::getLastErrors() !== false) {
            throw new DataEfException('invalid_source_timestamp');
        }

        $rate = is_int($average) || is_float($average) ? json_encode($average) : $average;

        if (! is_string($rate) || ! preg_match('/^\d{1,5}(?:\.\d{1,10})?$/D', $rate) || (float) $rate <= 0) {
            throw new DataEfException('invalid_rate');
        }

        return new ExchangeRateData(
            baseCurrency: $currency,
            unit: $unit,
            effectiveDate: $effectiveDay->format('Y-m-d'),
            averageRate: $rate,
            sourceRecordedAt: CarbonImmutable::instance($sourceRecordedAt)->utc(),
            rawHash: hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)),
        );
    }
}
