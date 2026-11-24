<?php

namespace App\Integrations\Gdt;

use App\Domain\ExchangeRate\Data\ExchangeRateData;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use UnexpectedValueException;

class GdtExchangeRateHistory
{
    /** @return non-empty-array<string, ExchangeRateData> */
    public function parse(string $html): array
    {
        $document = new DOMDocument;
        if ($html === '' || ! $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            throw new UnexpectedValueException('The downloaded GDT page could not be read.');
        }

        $xpath = new DOMXPath($document);
        $rows = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " exchange-rate-table-wrapper ")]//table/tbody/tr');

        if ($rows === false || $rows->length === 0 || $rows->length > 100) {
            throw new UnexpectedValueException('The GDT page did not contain a valid exchange-rate table.');
        }

        $rates = [];
        foreach ($rows as $row) {
            if (! $row instanceof DOMElement) {
                throw new UnexpectedValueException('The GDT page contains an invalid table row.');
            }

            $cells = $xpath->query('./td', $row);
            if ($cells === false || $cells->length !== 4) {
                throw new UnexpectedValueException('An exchange-rate row is missing required columns.');
            }

            $date = trim($cells->item(0)->textContent ?? '');
            $pair = trim($cells->item(1)->textContent ?? '');
            $value = trim($cells->item(2)->textContent ?? '');
            $publisher = trim($cells->item(3)->textContent ?? '');
            $day = DateTimeImmutable::createFromFormat('!F j, Y', $date);

            if ($pair !== 'USD/KHR' || $publisher !== 'National Bank of Cambodia'
                || $day === false || $day->format('F j, Y') !== $date
                || ! preg_match('/^[1-9]\d{0,4}(?:\.\d{1,10})?$/D', $value)) {
                throw new UnexpectedValueException('An exchange-rate row has an invalid date, quote, or publisher.');
            }

            $effectiveDate = $day->format('Y-m-d');
            if (isset($rates[$effectiveDate])) {
                throw new UnexpectedValueException('The GDT page contains duplicate publication dates.');
            }

            $rates[$effectiveDate] = new ExchangeRateData(
                baseCurrency: ExchangeRate::BASE_CURRENCY,
                unit: 1,
                effectiveDate: $effectiveDate,
                averageRate: $value,
                sourceRecordedAt: null,
                rawHash: hash('sha256', $effectiveDate.'|'.$pair.'|'.$value),
            );
        }

        if ($rates === []) {
            throw new UnexpectedValueException('The GDT page did not contain any USD/KHR quotes.');
        }

        return $rates;
    }
}
