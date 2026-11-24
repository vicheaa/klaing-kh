<?php

use App\Domain\DataSource\Models\DataSource;
use App\Domain\ExchangeRate\Actions\SyncExchangeRate;
use App\Domain\Ingestion\Models\SyncRun;
use App\Integrations\DataEf\DataEfClient;
use App\Integrations\DataEf\DataEfException;
use App\Jobs\DataSync\SyncNbcExchangeRate;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

const NBC_EXCHANGE_RATE_FIXTURE = __DIR__.'/../Fixtures/DataEf/nbc-exchange-rates.json';

beforeEach(function () {
    config([
        'dataef.sync_enabled' => true,
        'dataef.nbc_exchange_rate_enabled' => true,
    ]);
    Http::preventStrayRequests();
});

it('queues exchange rate syncs on ingestion only when permitted', function () {
    Queue::fake([SyncNbcExchangeRate::class]);

    config(['dataef.nbc_exchange_rate_enabled' => false]);
    $this->artisan('dataef:sync-exchange-rates')->assertFailed();
    Queue::assertNotPushed(SyncNbcExchangeRate::class);

    config(['dataef.nbc_exchange_rate_enabled' => true]);
    $this->artisan('dataef:sync-exchange-rates')->assertSuccessful();
    Queue::assertPushedOn('ingestion', SyncNbcExchangeRate::class);
});

it('logs the local scheduled time when the exchange rate sync starts', function () {
    Log::spy();
    $events = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event): bool => str_contains($event->command ?? '', 'dataef:sync-exchange-rates'));
    expect($events)->not->toBeEmpty();

    foreach ($events as $event) {
        $event->callBeforeCallbacks($this->app);
    }

    foreach ($events as $event) {
        Log::shouldHaveReceived('info', ['Scheduled DataEF exchange-rate sync starting.', [
            'scheduled_time' => $event->nextRunDate()->setTimezone('Asia/Phnom_Penh')->format('H:i'),
            'timezone' => 'Asia/Phnom_Penh',
        ]])->once();
    }
});

it('checks permission again when the job runs', function () {
    config(['dataef.sync_enabled' => false]);

    SyncNbcExchangeRate::dispatchSync();

    Http::assertNothingSent();
    $this->assertDatabaseCount('sync_runs', 0);
});

it('stores every quoted currency and corrects a repeated batch without duplicating it', function () {
    Log::spy();
    $this->travelTo(CarbonImmutable::parse('2026-11-21 12:00:00', 'UTC'));
    $payload = file_get_contents(NBC_EXCHANGE_RATE_FIXTURE);
    Http::fake([
        DataEfClient::EXCHANGE_RATE_URL => Http::sequence()
            ->push($payload)
            ->push(str_replace('4012.375', '4010.125', $payload)),
    ]);

    SyncNbcExchangeRate::dispatchSync();
    SyncNbcExchangeRate::dispatchSync();

    Http::assertSentCount(2);
    $this->assertDatabaseCount('exchange_rates', 3)
        ->assertDatabaseHas('exchange_rates', [
            'base_currency' => 'USD',
            'quote_currency' => 'KHR',
            'rate_type' => 'average',
            'effective_date' => '2026-11-20',
            'rate' => 4010.125,
            'source_recorded_at' => '2026-11-20 02:33:31',
        ])
        ->assertDatabaseHas('exchange_rates', ['base_currency' => 'JPY', 'unit' => 100, 'rate' => 2405])
        ->assertDatabaseHas('exchange_rates', ['base_currency' => 'IDR', 'unit' => 1000, 'rate' => 205])
        ->assertDatabaseHas('data_sources', [
            'slug' => DataSource::NBC_EXCHANGE_RATES,
            'last_synced_at' => '2026-11-21 12:00:00',
        ])
        ->assertDatabaseCount('sync_runs', 2);
    expect(SyncRun::query()->pluck('status')->all())->toBe(['success', 'success']);
    Log::shouldHaveReceived('info')->withArgs(
        fn (string $message, array $context): bool => $message === 'DataEF exchange-rate sync completed.'
            && $context['effective_date'] === '2026-11-20'
            && $context['records_processed'] === 3,
    )->twice();
});

it('preserves the last verified value when the next response is invalid', function (string $body, string $reason) {
    Log::spy();
    $this->travelTo(CarbonImmutable::parse('2026-11-21 12:00:00', 'UTC'));
    Http::fake([
        DataEfClient::EXCHANGE_RATE_URL => Http::sequence()
            ->push(file_get_contents(NBC_EXCHANGE_RATE_FIXTURE))
            ->push($body),
    ]);

    SyncNbcExchangeRate::dispatchSync();
    $this->travel(1)->day();

    expect(fn () => SyncNbcExchangeRate::dispatchSync())
        ->toThrow(DataEfException::class, "DataEF exchange-rate sync failed ({$reason}).");

    $this->assertDatabaseCount('exchange_rates', 3)
        ->assertDatabaseHas('exchange_rates', ['rate' => 4012.375])
        ->assertDatabaseHas('data_sources', ['last_synced_at' => '2026-11-21 12:00:00'])
        ->assertDatabaseHas('sync_runs', ['status' => 'failed', 'error_code' => $reason]);
    Log::shouldHaveReceived('warning')->withArgs(
        fn (string $message, array $context): bool => $message === 'DataEF exchange-rate sync failed.'
            && $context['error_code'] === $reason,
    )->once();
})->with([
    'malformed JSON' => ['oops', 'invalid_json'],
    'invalid quoted unit' => [fn () => str_replace('"unit": 100,', '"unit": 0,', file_get_contents(NBC_EXCHANGE_RATE_FIXTURE)), 'unexpected_currency_pair'],
    'invalid day' => [fn () => str_replace('2026-11-20', '2026-02-31', file_get_contents(NBC_EXCHANGE_RATE_FIXTURE)), 'invalid_effective_date'],
    'rate is zero' => [fn () => str_replace('4012.375', '0.0', file_get_contents(NBC_EXCHANGE_RATE_FIXTURE)), 'invalid_rate'],
    'mixed effective days' => [fn () => preg_replace('/2026-11-20/', '2026-11-19', file_get_contents(NBC_EXCHANGE_RATE_FIXTURE), 1), 'inconsistent_snapshot'],
    'missing baseline quote' => [fn () => str_replace(['"USD"', '"USD/KHR"'], ['"EUR"', '"EUR/KHR"'], file_get_contents(NBC_EXCHANGE_RATE_FIXTURE)), 'incomplete_snapshot'],
]);

it('waits for Retry-After and then recovers on the next permitted sync', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-21 12:00:00', 'UTC'));
    Http::fake([
        DataEfClient::EXCHANGE_RATE_URL => Http::sequence()
            ->pushStatus(429, ['Retry-After' => '600'])
            ->push(file_get_contents(NBC_EXCHANGE_RATE_FIXTURE)),
    ]);

    expect(fn () => SyncNbcExchangeRate::dispatchSync())->toThrow(DataEfException::class);
    SyncNbcExchangeRate::dispatchSync();

    Http::assertSentCount(1);
    $this->assertDatabaseHas('sync_runs', [
        'error_code' => 'rate_limited',
        'retry_after_at' => '2026-11-21 12:10:00',
    ]);

    $this->travel(10)->minutes();
    SyncNbcExchangeRate::dispatchSync();

    Http::assertSentCount(2);
    $this->assertDatabaseHas('sync_runs', ['status' => 'success']);
});

it('skips overlapping imports and marks an abandoned run on the next attempt', function () {
    $source = DataSource::factory()->create();
    SyncRun::query()->create([
        'data_source_id' => $source->id,
        'status' => SyncRun::RUNNING,
        'started_at' => now()->subMinutes(5),
    ]);
    Http::fake([
        DataEfClient::EXCHANGE_RATE_URL => Http::response(file_get_contents(NBC_EXCHANGE_RATE_FIXTURE)),
    ]);

    Cache::lock(SyncExchangeRate::LOCK_KEY, 60)->get(fn () => SyncNbcExchangeRate::dispatchSync());

    Http::assertNothingSent();

    SyncNbcExchangeRate::dispatchSync();

    $this->assertDatabaseHas('sync_runs', ['status' => 'failed', 'error_code' => 'abandoned'])
        ->assertDatabaseHas('sync_runs', ['status' => 'success']);
});
