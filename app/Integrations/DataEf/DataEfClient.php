<?php

namespace App\Integrations\DataEf;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DataEfClient
{
    public const string EXCHANGE_RATE_URL = 'https://data.mef.gov.kh/api/v1/realtime-api/exchange-rate';

    private const int MAX_RESPONSE_BYTES = 65536;

    public function fetchExchangeRates(): string
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->withOptions([
                    'allow_redirects' => false,
                    'progress' => static function (float $downloadSize, float $downloaded): void {
                        if (max($downloadSize, $downloaded) > self::MAX_RESPONSE_BYTES) {
                            throw new DataEfException('response_too_large');
                        }
                    },
                ])
                ->get(self::EXCHANGE_RATE_URL);
        } catch (ConnectionException) {
            throw new DataEfException('connection_failed');
        }

        if ($response->tooManyRequests()) {
            throw new DataEfException('rate_limited', $this->retryAfter($response->header('Retry-After')));
        }

        if (! $response->successful()) {
            throw new DataEfException('http_'.$response->status());
        }

        $body = $response->body();

        if (strlen($body) > self::MAX_RESPONSE_BYTES) {
            throw new DataEfException('response_too_large');
        }

        return $body;
    }

    private function retryAfter(string $header): CarbonImmutable
    {
        $now = CarbonImmutable::now('UTC');

        if (preg_match('/^\d{1,10}$/D', $header) === 1) {
            return $now->addSeconds((int) $header);
        }

        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::RFC7231, $header, new DateTimeZone('UTC'));

        return $date !== false
            ? CarbonImmutable::instance($date)
            : $now->addHour();
    }
}
