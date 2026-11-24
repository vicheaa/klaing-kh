<?php

namespace App\Domain\Ingestion\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['data_source_id', 'status', 'started_at', 'finished_at', 'error_code', 'retry_after_at'])]
class SyncRun extends Model
{
    public const string RUNNING = 'running';

    public const string SUCCESS = 'success';

    public const string FAILED = 'failed';
}
