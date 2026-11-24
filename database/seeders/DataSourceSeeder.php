<?php

namespace Database\Seeders;

use App\Domain\DataSource\Models\DataSource;
use Illuminate\Database\Seeder;

class DataSourceSeeder extends Seeder
{
    public function run(): void
    {
        DataSource::registerNbcExchangeRates();
    }
}
