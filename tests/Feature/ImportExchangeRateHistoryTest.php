<?php

use App\Domain\DataSource\Models\DataSource;
use App\Domain\ExchangeRate\Models\ExchangeRate;

it('imports dated official USD/KHR quotes with their own provenance and can be repeated', function () {
    $file = __DIR__.'/../Fixtures/Gdt/daily-exchange-rates.html';

    $this->artisan('exchange-rates:import-history', ['file' => $file])->assertSuccessful();
    $this->artisan('exchange-rates:import-history', ['file' => $file])->assertSuccessful();

    $archive = DataSource::query()->where('slug', DataSource::GDT_EXCHANGE_RATE_HISTORY)->firstOrFail();
    $this->assertDatabaseCount('exchange_rates', 2)
        ->assertDatabaseHas('exchange_rates', [
            'data_source_id' => $archive->id,
            'base_currency' => 'USD',
            'quote_currency' => 'KHR',
            'rate_type' => ExchangeRate::OFFICIAL_RATE_TYPE,
            'effective_date' => '2026-11-18',
            'rate' => 4010.25,
            'unit' => 1,
            'source_recorded_at' => null,
        ])
        ->assertDatabaseHas('sync_runs', ['data_source_id' => $archive->id, 'status' => 'success']);
    expect($archive->access_provider)->toBe('General Department of Taxation');
});

it('rejects an unrelated download without storing any records', function () {
    $this->artisan('exchange-rates:import-history', [
        'file' => __DIR__.'/../Fixtures/DataEf/nbc-exchange-rates.json',
    ])->assertFailed();

    $this->assertDatabaseCount('data_sources', 0)
        ->assertDatabaseCount('exchange_rates', 0);
});
