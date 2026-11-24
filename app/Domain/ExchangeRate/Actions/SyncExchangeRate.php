<?php

namespace App\Domain\ExchangeRate\Actions;

use App\Domain\DataSource\Models\DataSource;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use App\Domain\Ingestion\Models\SyncRun;
use App\Integrations\DataEf\DataEfException;
use App\Integrations\DataEf\NbcExchangeRateAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncExchangeRate
{
    public const string LOCK_KEY = 'dataef:nbc-exchange-rates';

    public function __construct(
        private NbcExchangeRateAdapter $adapter,
        private UpsertExchangeRates $upsert,
    ) {}

    public static function isEnabled(): bool
    {
        return Config::boolean('dataef.sync_enabled') && Config::boolean('dataef.nbc_exchange_rate_enabled');
    }

    public function handle(): void
    {
        if (! self::isEnabled()) {
            return;
        }

        Cache::lock(self::LOCK_KEY, 60)->get(function (): void {
            $this->sync(DataSource::registerNbcExchangeRates());
        });
    }

    private function sync(DataSource $source): void
    {
        $startedAt = now();
        SyncRun::query()
            ->where('data_source_id', $source->id)
            ->where('status', SyncRun::RUNNING)
            ->update(['status' => SyncRun::FAILED, 'finished_at' => $startedAt, 'error_code' => 'abandoned']);

        if (SyncRun::query()->where('data_source_id', $source->id)->where('retry_after_at', '>', $startedAt)->exists()) {
            return;
        }

        $run = SyncRun::query()->create([
            'data_source_id' => $source->id,
            'status' => SyncRun::RUNNING,
            'started_at' => $startedAt,
        ]);

        try {
            $rates = $this->adapter->fetch();
            DB::transaction(function () use ($source, $run, $rates): void {
                $this->upsert->handle($source, $rates, ExchangeRate::RATE_TYPE);

                $finishedAt = now();
                $source->update(['last_synced_at' => $finishedAt]);
                $run->update(['status' => SyncRun::SUCCESS, 'finished_at' => $finishedAt]);
            });
        } catch (Throwable $exception) {
            $errorCode = $exception instanceof DataEfException ? $exception->reason : 'sync_failed';
            $run->update([
                'status' => SyncRun::FAILED,
                'finished_at' => now(),
                'error_code' => $errorCode,
                'retry_after_at' => $exception instanceof DataEfException ? $exception->retryAfter : null,
            ]);
            Log::warning('DataEF exchange-rate sync failed.', [
                'sync_run_id' => $run->id,
                'error_code' => $errorCode,
            ]);

            throw $exception;
        }

        Log::info('DataEF exchange-rate sync completed.', [
            'sync_run_id' => $run->id,
            'effective_date' => $rates[ExchangeRate::BASE_CURRENCY]->effectiveDate,
            'records_processed' => count($rates),
        ]);
    }
}
