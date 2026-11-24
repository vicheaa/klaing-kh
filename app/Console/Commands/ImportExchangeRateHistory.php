<?php

namespace App\Console\Commands;

use App\Domain\DataSource\Models\DataSource;
use App\Domain\ExchangeRate\Actions\UpsertExchangeRates;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use App\Domain\Ingestion\Models\SyncRun;
use App\Integrations\Gdt\GdtExchangeRateHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

class ImportExchangeRateHistory extends Command
{
    protected $signature = 'exchange-rates:import-history {file : Downloaded English GDT exchange-rates page}';

    protected $description = 'Import verified USD/KHR history from a downloaded GDT page';

    public function handle(GdtExchangeRateHistory $parser, UpsertExchangeRates $upsert): int
    {
        $file = $this->argument('file');
        if (! is_file($file) || ! is_readable($file)) {
            $this->error('Choose a readable local GDT exchange-rates page.');

            return self::FAILURE;
        }

        $maxBytes = 1024 * 1024;
        $html = file_get_contents($file, length: $maxBytes + 1);
        if ($html === false || strlen($html) > $maxBytes) {
            $this->error('The GDT page could not be read within the 1 MiB limit.');

            return self::FAILURE;
        }

        try {
            $rates = $parser->parse($html);
        } catch (UnexpectedValueException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        DB::transaction(function () use ($rates, $upsert): void {
            $source = DataSource::query()->firstOrCreate(
                ['slug' => DataSource::GDT_EXCHANGE_RATE_HISTORY],
                DataSource::GDT_ATTRIBUTION,
            );
            $upsert->handle($source, $rates, ExchangeRate::OFFICIAL_RATE_TYPE);

            $syncedAt = now();
            $source->update(['last_synced_at' => $syncedAt]);
            SyncRun::query()->create([
                'data_source_id' => $source->id,
                'status' => SyncRun::SUCCESS,
                'started_at' => $syncedAt,
                'finished_at' => $syncedAt,
            ]);
        });

        $this->info(sprintf('Imported %d verified daily USD/KHR rates.', count($rates)));

        return self::SUCCESS;
    }
}
