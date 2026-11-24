<?php

namespace Database\Seeders;

use App\Domain\AdministrativeArea\Actions\ImportAdministrativeAreas;
use Illuminate\Database\Seeder;

class AdministrativeAreaSeeder extends Seeder
{
    public function run(ImportAdministrativeAreas $import): void
    {
        $import->handle(public_path('assets'));
    }
}
