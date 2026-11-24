<?php

namespace App\Http\Controllers\Public;

use App\Domain\AdministrativeArea\Models\AdministrativeArea;
use App\Domain\AdministrativeArea\Services\AdministrativeAreaQueries;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdministrativeAreaIndexRequest;
use App\Http\Resources\AdministrativeAreaResource;
use Inertia\Inertia;
use Inertia\Response;

class AdministrativeAreaController extends Controller
{
    public function __invoke(AdministrativeAreaIndexRequest $request, AdministrativeAreaQueries $queries): Response
    {
        $filters = $request->filters();
        $search = $filters['q'] ?? null;
        $level = $filters['level'] ?? ($search === null ? 'province' : null);
        $includesProvince = $level !== null && $level !== 'province';
        $includesDistrict = in_array($level, ['commune', 'village'], true);
        $commune = $level === 'village' ? ($filters['commune_code'] ?? null) : null;
        $district = $includesDistrict
            ? ($filters['district_code'] ?? ($commune === null ? null : substr($commune, 0, 4)))
            : null;
        $province = $includesProvince
            ? ($filters['province_code'] ?? ($district === null ? null : substr($district, 0, 2)))
            : null;

        $scope = [
            'level' => $level,
            'province_code' => $province,
            'district_code' => $district,
            'commune_code' => $commune,
            'q' => $search,
        ];
        $areas = $queries->paginate([...$scope, 'page' => $filters['page'] ?? 1], 15);

        return Inertia::render('administrative-areas', [
            'areas' => AdministrativeAreaResource::collection($areas->getCollection())->resolve($request),
            'counts' => $queries->counts(),
            'options' => [
                'province' => $includesProvince ? $queries->options('province') : [],
                'district' => $includesDistrict ? $queries->options('district', $province) : [],
                'commune' => $level === 'village' ? $queries->options('commune', $district) : [],
            ],
            'filters' => $scope,
            'pagination' => [
                'page' => $areas->currentPage(),
                'last_page' => $areas->lastPage(),
                'first_item' => $areas->firstItem(),
                'last_item' => $areas->lastItem(),
                'total' => $areas->total(),
            ],
            'datasetYear' => AdministrativeArea::DATASET_YEAR,
            'language' => $filters['lang'] ?? 'en',
        ]);
    }
}
