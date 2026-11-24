<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\ExchangeRate\Services\ExchangeRateQueries;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExchangeRates\ExchangeRateHistoryRequest;
use App\Http\Resources\ExchangeRateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExchangeRateController extends Controller
{
    public function latest(Request $request, ExchangeRateQueries $queries): JsonResponse
    {
        $source = $queries->source();
        $latest = $queries->latest($source);

        return response()->json([
            'data' => $latest === null ? null : ExchangeRateResource::make($latest)->resolve($request),
            'meta' => $queries->metadata($source, $latest),
        ]);
    }

    public function history(ExchangeRateHistoryRequest $request, ExchangeRateQueries $queries): AnonymousResourceCollection
    {
        $filters = $request->filters();
        $source = $queries->source();
        $history = $queries->history($source, $filters['from'] ?? null, $filters['to'] ?? null, (int) ($filters['per_page'] ?? 30));

        return ExchangeRateResource::collection($history->appends($filters))
            ->additional(['meta' => $queries->metadata($source, $queries->latest($source))]);
    }
}
