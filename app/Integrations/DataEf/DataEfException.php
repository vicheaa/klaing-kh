<?php

namespace App\Integrations\DataEf;

use Carbon\CarbonImmutable;
use RuntimeException;

class DataEfException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        public readonly ?CarbonImmutable $retryAfter = null,
    ) {
        parent::__construct("DataEF exchange-rate sync failed ({$reason}).");
    }
}
