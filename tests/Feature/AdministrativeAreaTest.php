<?php

use App\Domain\AdministrativeArea\Actions\ImportAdministrativeAreas;
use App\Domain\AdministrativeArea\Models\AdministrativeArea;
use Database\Seeders\AdministrativeAreaSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

it('imports the complete source dataset repeatedly without losing ambiguous village codes', function () {
    $this->seed(AdministrativeAreaSeeder::class);
    $this->seed(AdministrativeAreaSeeder::class);

    $this->assertDatabaseCount('administrative_areas', 16472);
    expect(AdministrativeArea::query()->where('level', 'province')->count())->toBe(25)
        ->and(AdministrativeArea::query()->where('level', 'district')->count())->toBe(210)
        ->and(AdministrativeArea::query()->where('level', 'commune')->count())->toBe(1661)
        ->and(AdministrativeArea::query()->where('level', 'village')->count())->toBe(14576);

    $this->getJson(route('api.v1.administrative-areas.index', [
        'level' => 'village',
        'commune_code' => '120913',
        'q' => '12091301',
    ]))->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('data.0.code', '12091301')
        ->assertJsonPath('data.0.commune_code', '120913')
        ->assertJsonPath('data.0.commune', null)
        ->assertJsonPath('data.0.district.code', '1209')
        ->assertJsonPath('meta.total', 5);

    $this->getJson(route('api.v1.administrative-areas.index', ['level' => 'village', 'q' => '0506140-']))
        ->assertOk()
        ->assertJsonPath('meta.total', 9);
});

it('rolls back earlier levels if a later file has an inconsistent parent', function () {
    $disk = Storage::fake('local');
    $districtFile = 'CambodiaDistrictList2025.csv';
    $disk->put('CambodiaProvinceList2025.csv', "province_code,province_kh,province_en\n01,ខេត្ត,Example Province\n");
    $disk->put($districtFile, "province_code,district_code,district_kh,district_en\n02,0201,ស្រុក,Example District\n");

    expect(fn () => app(ImportAdministrativeAreas::class)->handle($disk->path('')))
        ->toThrow(RuntimeException::class, "Inconsistent parent codes in {$districtFile}:2.");

    $this->assertDatabaseCount('administrative_areas', 0);
});

it('opens the directory to guests in Khmer and keeps the selected hierarchy', function () {
    AdministrativeArea::factory()->create(['id' => '01', 'code' => '01', 'name_en' => 'Example Province']);
    AdministrativeArea::factory()->create([
        'id' => '0101',
        'level' => 'district',
        'code' => '0101',
        'province_code' => '01',
    ]);
    AdministrativeArea::factory()->create([
        'id' => '0201',
        'level' => 'district',
        'code' => '0201',
        'province_code' => AdministrativeArea::factory()->create(['id' => '02', 'code' => '02'])->id,
    ]);

    $this->get(route('administrative-areas.index', ['level' => 'district', 'province_code' => '01', 'lang' => 'km']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('administrative-areas')
            ->where('language', 'km')
            ->where('counts.province', 2)
            ->where('counts.district', 2)
            ->where('filters.province_code', '01')
            ->has('options.province', 2)
            ->has('areas', 1)
            ->where('areas.0.code', '0101')
            ->where('areas.0.province.name_en', 'Example Province'));
});

it('returns an empty public directory before the source data is seeded', function () {
    $this->get(route('administrative-areas.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('administrative-areas')
            ->where('counts.province', 0)
            ->where('areas', [])
            ->where('pagination.total', 0));
});

it('searches all four levels together and lets visitors narrow the same results by level', function () {
    AdministrativeArea::factory()->createMany([
        ['id' => '01', 'code' => '01', 'name_en' => 'Srae Province'],
        ['id' => '0101', 'level' => 'district', 'code' => '0101', 'province_code' => '01', 'name_en' => 'Srae District'],
        ['id' => '010101', 'level' => 'commune', 'code' => '010101', 'province_code' => '01', 'district_code' => '0101', 'name_en' => 'Srae Commune'],
        [
            'id' => hash('sha256', 'Srae Village'),
            'level' => 'village',
            'code' => '01010101',
            'province_code' => '01',
            'district_code' => '0101',
            'commune_code' => '010101',
            'name_en' => 'Srae Village',
        ],
        ['id' => '02', 'code' => '02', 'name_en' => 'Other Province'],
    ]);

    $this->get(route('administrative-areas.index', ['q' => 'srae']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('administrative-areas')
            ->where('filters.level', null)
            ->where('filters.q', 'srae')
            ->has('areas', 4)
            ->where('areas.0.level', 'province')
            ->where('areas.1.level', 'district')
            ->where('areas.2.level', 'commune')
            ->where('areas.3.level', 'village')
            ->where('areas.3.commune.code', '010101'));

    $this->get(route('administrative-areas.index', ['q' => 'srae', 'level' => 'village']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.level', 'village')
            ->where('filters.q', 'srae')
            ->has('areas', 1)
            ->where('areas.0.name_en', 'Srae Village'));
});

it('searches and paginates the public API independently of the market data switch', function () {
    config(['dataef.external_api_enabled' => false]);
    AdministrativeArea::factory()->create([
        'id' => '01', 'code' => '01', 'name_km' => 'ខេត្ត ក', 'name_en' => 'North Province',
    ]);
    AdministrativeArea::factory()->create([
        'id' => '02', 'code' => '02', 'name_km' => 'ខេត្ត ខ', 'name_en' => 'South Province',
    ]);

    $this->getJson(route('api.v1.administrative-areas.index', ['q' => 'PROVINCE', 'per_page' => 1]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', '01')
        ->assertJsonPath('meta.dataset_year', 2025)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('links.next', route('api.v1.administrative-areas.index', ['q' => 'PROVINCE', 'per_page' => 1, 'page' => 2]));

    $this->getJson(route('api.v1.administrative-areas.index', ['q' => 'ខេត្ត ខ']))
        ->assertOk()
        ->assertJsonPath('data.0.id', '02');

    $this->getJson(route('api.v1.administrative-areas.index', ['q' => '%']))
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->getJson(route('api.v1.administrative-areas.show', '01'))
        ->assertOk()
        ->assertJsonPath('data.name_km', 'ខេត្ត ក')
        ->assertJsonPath('data.province', null);

    $this->getJson(route('api.v1.administrative-areas.show', '99'))->assertNotFound();
});

it('validates public API filters and disallows writes', function () {
    $url = route('api.v1.administrative-areas.index');

    $this->get($url.'?level=country&province_code=1&per_page=101&page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['level', 'province_code', 'per_page', 'page']);

    $this->postJson($url)->assertMethodNotAllowed();
});
