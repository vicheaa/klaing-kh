<?php

namespace App\Jobs\DataSync;

use App\Domain\ExchangeRate\Actions\SyncExchangeRate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncNbcExchangeRate implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('ingestion');
    }

    public function handle(SyncExchangeRate $sync): void
    {
        $sync->handle();
    }
}
