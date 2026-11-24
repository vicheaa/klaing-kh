<?php

namespace App\Console\Commands;

use App\Domain\ExchangeRate\Actions\SyncExchangeRate;
use App\Jobs\DataSync\SyncNbcExchangeRate;
use Illuminate\Console\Command;

class SyncExchangeRates extends Command
{
    protected $signature = 'dataef:sync-exchange-rates';

    protected $description = 'Sync the National Bank of Cambodia currency rates against the riel';

    public function handle(): int
    {
        if (! SyncExchangeRate::isEnabled()) {
            $this->warn('Exchange-rate sync is disabled. Check DATAEF_SYNC_ENABLED and DATAEF_NBC_EXCHANGE_RATE_ENABLED.');

            return self::FAILURE;
        }

        SyncNbcExchangeRate::dispatch();

        $this->info('Exchange-rate sync queued on ingestion.');

        return self::SUCCESS;
    }
}
