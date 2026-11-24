<?php

namespace Database\Factories;

use App\Domain\DataSource\Models\DataSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DataSource> */
class DataSourceFactory extends Factory
{
    protected $model = DataSource::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'slug' => DataSource::NBC_EXCHANGE_RATES,
            ...DataSource::NBC_ATTRIBUTION,
        ];
    }
}
