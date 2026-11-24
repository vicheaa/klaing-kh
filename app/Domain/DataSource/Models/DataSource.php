<?php

namespace App\Domain\DataSource\Models;

use Database\Factories\DataSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['slug', 'access_provider', 'original_publisher', 'source_url', 'last_synced_at'])]
#[UseFactory(DataSourceFactory::class)]
class DataSource extends Model
{
    /** @use HasFactory<DataSourceFactory> */
    use HasFactory;

    public const string NBC_EXCHANGE_RATES = 'dataef-nbc-exchange-rates';

    public const string GDT_EXCHANGE_RATE_HISTORY = 'gdt-nbc-exchange-rate-history';

    public const array NBC_ATTRIBUTION = [
        'access_provider' => 'DataEF',
        'original_publisher' => 'National Bank of Cambodia',
        'source_url' => 'https://data.mef.gov.kh/datasets/pd_66a0cd503e0bd300012638fb4',
    ];

    public const array GDT_ATTRIBUTION = [
        'access_provider' => 'General Department of Taxation',
        'original_publisher' => 'National Bank of Cambodia',
        'source_url' => 'https://www.tax.gov.kh/gdtwebsiteweb/en/exchange-rate',
    ];

    public static function registerNbcExchangeRates(): self
    {
        return self::query()->firstOrCreate(['slug' => self::NBC_EXCHANGE_RATES], self::NBC_ATTRIBUTION);
    }

    /** @return array{last_synced_at: 'immutable_datetime'} */
    protected function casts(): array
    {
        return ['last_synced_at' => 'immutable_datetime'];
    }
}
