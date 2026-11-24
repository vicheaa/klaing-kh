<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\AdministrativeArea\Models\AdministrativeArea;
use App\Domain\AdministrativeArea\Services\AdministrativeAreaQueries;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdministrativeAreaIndexRequest;
use App\Http\Resources\AdministrativeAreaResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdministrativeAreaController extends Controller
{
    public function index(AdministrativeAreaIndexRequest $request, AdministrativeAreaQueries $queries): AnonymousResourceCollection
    {
        $filters = $request->filters();
        $areas = $queries->paginate($filters, 25)->appends($filters);

        return AdministrativeAreaResource::collection($areas)
            ->additional(['meta' => ['dataset_year' => AdministrativeArea::DATASET_YEAR]]);
    }

    public function show(string $area, AdministrativeAreaQueries $queries): AdministrativeAreaResource
    {
        return AdministrativeAreaResource::make($queries->withAncestors()->findOrFail($area))
            ->additional(['meta' => ['dataset_year' => AdministrativeArea::DATASET_YEAR]]);
    }
}
