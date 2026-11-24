<?php

namespace App\Domain\AdministrativeArea\Models;

use Database\Factories\AdministrativeAreaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $level
 * @property string $code
 * @property string $name_km
 * @property string $name_en
 * @property string|null $province_code
 * @property string|null $district_code
 * @property string|null $commune_code
 */
class AdministrativeArea extends Model
{
    /** @use HasFactory<AdministrativeAreaFactory> */
    use HasFactory;

    public const int DATASET_YEAR = 2025;

    public const array LEVELS = ['province', 'district', 'commune', 'village'];

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected static function newFactory(): AdministrativeAreaFactory
    {
        return AdministrativeAreaFactory::new();
    }

    /** @return BelongsTo<AdministrativeArea, $this> */
    public function province(): BelongsTo
    {
        return $this->belongsTo(self::class, 'province_code');
    }

    /** @return BelongsTo<AdministrativeArea, $this> */
    public function district(): BelongsTo
    {
        return $this->belongsTo(self::class, 'district_code');
    }

    /** @return BelongsTo<AdministrativeArea, $this> */
    public function commune(): BelongsTo
    {
        return $this->belongsTo(self::class, 'commune_code');
    }
}
