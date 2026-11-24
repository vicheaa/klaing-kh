<?php

namespace Database\Factories;

use App\Domain\AdministrativeArea\Models\AdministrativeArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdministrativeArea> */
class AdministrativeAreaFactory extends Factory
{
    protected $model = AdministrativeArea::class;

    /** @return array<string, string> */
    public function definition(): array
    {
        $code = fake()->unique()->numerify('##');

        return [
            'id' => $code,
            'level' => 'province',
            'code' => $code,
            'name_km' => 'កំពង់ចាម',
            'name_en' => fake()->city(),
        ];
    }
}
