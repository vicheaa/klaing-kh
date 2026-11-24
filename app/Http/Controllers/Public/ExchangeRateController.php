<?php

namespace App\Http\Controllers\Public;

use App\Domain\DataSource\Models\DataSource;
use App\Domain\ExchangeRate\Data\CurrencyPair;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use App\Domain\ExchangeRate\Services\ExchangeRateQueries;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExchangeRates\CurrencyPairHistoryRequest;
use Inertia\Inertia;
use Inertia\Response;

class ExchangeRateController extends Controller
{
    public function __invoke(CurrencyPairHistoryRequest $request, ExchangeRateQueries $queries): Response
    {
        $filters = $request->filters();
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $source = $queries->source();
        $pair = $request->pair();
        $currencies = $queries->currencies($source);

        abort_unless(in_array($pair->base, $currencies, true) && in_array($pair->quote, $currencies, true), 404);

        $archive = $pair->includesRiel() && $pair->foreignCurrency() === ExchangeRate::BASE_CURRENCY
            ? $queries->archive()
            : null;
        $latest = $queries->latest($source, $archive, $pair);
        $history = $queries->history($source, $from, $to, 30, $archive, $pair);
        $latestSource = $archive !== null && $latest?->data_source_id === $archive->id ? $archive : $source;

        return Inertia::render('exchange-rates', [
            'pair' => ['base' => $pair->base, 'quote' => $pair->quote],
            'currencies' => $currencies,
            'latest' => $latest === null ? null : $this->displayRate($pair, $latest),
            'dailyTrend' => $queries->dailyTrend($source, $from, $to, $archive, $pair)
                ->map(fn (ExchangeRate $rate): array => $this->displayRate($pair, $rate))
                ->all(),
            'meta' => $queries->metadata($latestSource, $latest),
            'archiveUrl' => $archive?->source_url,
            'history' => $history->getCollection()->map(fn (ExchangeRate $rate): array => [
                ...$this->displayRate($pair, $rate),
                'access_provider' => $rate->data_source_id === $archive?->id
                    ? DataSource::GDT_ATTRIBUTION['access_provider']
                    : DataSource::NBC_ATTRIBUTION['access_provider'],
            ])->all(),
            'filters' => ['from' => $from, 'to' => $to],
            'pagination' => ['page' => $history->currentPage(), 'last_page' => $history->lastPage()],
            'language' => $filters['lang'] ?? 'en',
        ]);
    }

    /** @return array{base_currency: string, quote_currency: string, effective_date: string, rate: string, unit: int} */
    private function displayRate(CurrencyPair $pair, ExchangeRate $rate): array
    {
        return [
            'base_currency' => $pair->base,
            'quote_currency' => $pair->quote,
            'effective_date' => $rate->effective_date,
            'rate' => $pair->rate($rate),
            'unit' => 1,
        ];
    }
}
