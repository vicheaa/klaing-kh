<?php

use App\Domain\DataSource\Models\DataSource;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use App\Domain\Ingestion\Models\SyncRun;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

it('shows an empty exchange-rate page to guests without an upstream request', function () {
    config(['inertia.ssr.enabled' => false]);
    Http::preventStrayRequests();

    $this->get(route('exchange-rates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('exchange-rates')
            ->where('latest', null)
            ->where('dailyTrend', [])
            ->where('history', [])
            ->where('meta.original_publisher', 'National Bank of Cambodia')
            ->where('meta.last_synced_at', null));
});

it('shows the latest effective rate independently of the filtered history in Khmer', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-24 10:00:00', 'UTC'));
    $source = DataSource::factory()->create();
    ExchangeRate::factory()->create(['data_source_id' => $source->id, 'effective_date' => '2026-11-20']);
    ExchangeRate::factory()->create(['data_source_id' => $source->id, 'effective_date' => '2026-11-21', 'rate' => '4020.375']);
    ExchangeRate::factory()->create(['data_source_id' => $source->id, 'effective_date' => '2026-11-25']);
    SyncRun::query()->create([
        'data_source_id' => $source->id,
        'status' => SyncRun::FAILED,
        'error_code' => 'connection_failed',
        'started_at' => now(),
    ]);

    $this->get(route('exchange-rates.index', ['lang' => 'km', 'to' => '2026-11-20']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('exchange-rates')
            ->where('language', 'km')
            ->where('latest.effective_date', '2026-11-21')
            ->where('latest.rate', '4020.3750000000')
            ->where('meta.is_stale', true)
            ->where('meta.sync_failed', true)
            ->has('history', 1)
            ->where('history.0.effective_date', '2026-11-20'));
});

it('keeps the external latest and history APIs closed when disabled', function (string $name) {
    config(['dataef.external_api_enabled' => false]);

    $this->getJson(route($name))->assertNotFound();
})->with(['api.v1.exchange-rates.latest', 'api.v1.exchange-rates.history']);

it('converts stored quotes for the selected pair in the correct direction', function (string $base, string $quote, string $date, string $rate) {
    $this->travelTo(CarbonImmutable::parse('2026-11-22 10:00:00', 'UTC'));
    $source = DataSource::factory()->create();
    $rates = ExchangeRate::factory()->state(['data_source_id' => $source->id]);
    $rates->create(['base_currency' => 'EUR', 'effective_date' => '2026-11-20', 'rate' => '5000']);
    $rates->create(['base_currency' => 'JPY', 'effective_date' => '2026-11-20', 'rate' => '2500', 'unit' => 100]);
    $rates->create(['base_currency' => 'JPY', 'effective_date' => '2026-11-21', 'rate' => '2000', 'unit' => 100]);

    $this->get(route('exchange-rates.index', ['base' => $base, 'quote' => $quote, 'from' => $date]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('pair', ['base' => $base, 'quote' => $quote])
            ->where('currencies', ['EUR', 'JPY', 'KHR', 'USD'])
            ->where('latest.rate', $rate)
            ->where('latest.effective_date', $date)
            ->has('dailyTrend', 1)
            ->where('dailyTrend.0.rate', $rate)
            ->has('history', 1)
            ->where('history.0.rate', $rate));
})->with([
    'cross rate uses the last common date and quoted units' => ['EUR', 'JPY', '2026-11-20', '200'],
    'reverse cross rate' => ['JPY', 'EUR', '2026-11-20', '0.005'],
    'riel per one unit' => ['JPY', 'KHR', '2026-11-21', '20'],
    'foreign currency per riel' => ['KHR', 'JPY', '2026-11-21', '0.05'],
]);

it('leaves chart history empty when the selected currencies have no common publication date', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-22 10:00:00', 'UTC'));
    $source = DataSource::factory()->create();
    ExchangeRate::factory()->create(['data_source_id' => $source->id, 'base_currency' => 'EUR', 'effective_date' => '2026-11-20']);
    ExchangeRate::factory()->create(['data_source_id' => $source->id, 'base_currency' => 'JPY', 'effective_date' => '2026-11-21']);

    $this->get(route('exchange-rates.index', ['base' => 'EUR', 'quote' => 'JPY']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('latest', null)
            ->where('dailyTrend', [])
            ->where('history', []));
});

it('rejects a currency that has no stored rate', function () {
    $this->get(route('exchange-rates.index', ['base' => 'EUR']))->assertNotFound();
});

it('allows two identical currencies without generating market history', function () {
    $this->get(route('exchange-rates.index', ['base' => 'KHR', 'quote' => 'KHR']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('pair', ['base' => 'KHR', 'quote' => 'KHR'])
            ->where('latest', null)
            ->where('dailyTrend', []));
});

it('charts daily USD/KHR only, using the archive for dates missing from the live feed', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-24 10:00:00', 'UTC'));
    $source = DataSource::factory()->create();
    $rates = ExchangeRate::factory()->state(['data_source_id' => $source->id]);
    $rates->create(['effective_date' => '2026-07-01']);
    $rates->create(['effective_date' => '2026-11-20']);
    $rates->create(['effective_date' => '2026-11-23', 'rate' => '4021']);
    $rates->create(['base_currency' => 'JPY', 'unit' => 100, 'rate' => '2450', 'effective_date' => '2026-11-20']);
    $rates->create(['base_currency' => 'JPY', 'unit' => 100, 'rate' => '2500', 'effective_date' => '2026-11-23']);
    $rates->create(['base_currency' => 'EUR', 'effective_date' => '2026-11-25']);
    $archive = DataSource::factory()->create(['slug' => DataSource::GDT_EXCHANGE_RATE_HISTORY, ...DataSource::GDT_ATTRIBUTION]);
    $archivedRates = ExchangeRate::factory()->state([
        'data_source_id' => $archive->id,
        'rate_type' => ExchangeRate::OFFICIAL_RATE_TYPE,
    ]);
    $archivedRates->create(['effective_date' => '2026-11-19', 'rate' => '4025']);
    $archivedRates->create(['effective_date' => '2026-11-23', 'rate' => '4999']);

    $this->get(route('exchange-rates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('latestRates')
            ->has('dailyTrend', 3)
            ->where('dailyTrend.0.effective_date', '2026-11-19')
            ->where('dailyTrend.0.rate', '4025.0000000000')
            ->where('dailyTrend.2.effective_date', '2026-11-23')
            ->where('dailyTrend.2.rate', '4021.0000000000')
            ->has('history', 4)
            ->where('history.0.access_provider', 'DataEF')
            ->where('history.2.access_provider', 'General Department of Taxation'));
});

it('serves an attributed decimal string for the latest exchange rate when enabled', function () {
    config(['dataef.external_api_enabled' => true]);
    $this->travelTo(CarbonImmutable::parse('2026-11-21 10:00:00', 'UTC'));
    ExchangeRate::factory()->create(['effective_date' => '2026-11-20', 'rate' => '4012.375']);

    $this->getJson(route('api.v1.exchange-rates.latest'))
        ->assertOk()
        ->assertJsonPath('data.rate', '4012.3750000000')
        ->assertJsonPath('data.base_currency', 'USD')
        ->assertJsonPath('data.quote_currency', 'KHR')
        ->assertJsonPath('meta.original_publisher', 'National Bank of Cambodia')
        ->assertJsonPath('meta.source_url', DataSource::NBC_ATTRIBUTION['source_url'])
        ->assertJsonPath('meta.is_stale', false);
});

it('paginates filtered history and preserves its filter in the next link', function () {
    config(['dataef.external_api_enabled' => true]);
    $this->travelTo(CarbonImmutable::parse('2026-11-22 12:00:00', 'UTC'));
    $source = DataSource::factory()->create();
    foreach (['2026-11-20', '2026-11-21', '2026-11-22'] as $date) {
        ExchangeRate::factory()->create(['data_source_id' => $source->id, 'effective_date' => $date]);
    }

    $response = $this->getJson(route('api.v1.exchange-rates.history', ['to' => '2026-11-21', 'per_page' => 1]));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.effective_date', '2026-11-21')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.access_provider', 'DataEF');
    expect($response->json('links.next'))->toContain('to=2026-11-21', 'page=2');
});

it('rejects invalid history filters', function (array $filters, string $field) {
    config(['dataef.external_api_enabled' => true]);

    $this->getJson(route('api.v1.exchange-rates.history', $filters))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'reversed dates' => [['from' => '2026-11-21', 'to' => '2026-11-20'], 'to'],
    'invalid date' => [['from' => '2026-02-31'], 'from'],
    'oversized page' => [['per_page' => 101], 'per_page'],
]);
