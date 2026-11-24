<?php

namespace App\Domain\AdministrativeArea\Services;

use App\Domain\AdministrativeArea\Models\AdministrativeArea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AdministrativeAreaQueries
{
    /** @return Builder<AdministrativeArea> */
    public function withAncestors(): Builder
    {
        return AdministrativeArea::query()->with([
            'province:id,code,name_en,name_km',
            'district:id,code,name_en,name_km',
            'commune:id,code,name_en,name_km',
        ]);
    }

    /**
     * @param  array{level?: string|null, q?: string|null, province_code?: string|null, district_code?: string|null, commune_code?: string|null, per_page?: int|string, page?: int|string}  $filters
     * @return LengthAwarePaginator<int, AdministrativeArea>
     */
    public function paginate(array $filters, int $defaultPageSize): LengthAwarePaginator
    {
        $query = $this->withAncestors();

        foreach (['level', 'province_code', 'district_code', 'commune_code'] as $column) {
            if (isset($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        if (isset($filters['q'])) {
            $term = '%'.strtr(Str::lower($filters['q']), ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $query->where(fn (Builder $matches): Builder => $matches
                ->whereRaw("LOWER(name_en) LIKE ? ESCAPE '!'", [$term])
                ->orWhereRaw("LOWER(name_km) LIKE ? ESCAPE '!'", [$term])
                ->orWhereRaw("code LIKE ? ESCAPE '!'", [$term]));
        }

        return $query->orderBy('code')->orderBy('id')->paginate(
            perPage: (int) ($filters['per_page'] ?? $defaultPageSize),
            page: (int) ($filters['page'] ?? 1),
        );
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        $counts = AdministrativeArea::query()
            ->selectRaw('level, count(*) as total')
            ->groupBy('level')
            ->toBase()
            ->pluck('total', 'level');

        return array_replace(array_fill_keys(AdministrativeArea::LEVELS, 0), array_map('intval', $counts->all()));
    }

    /** @return list<array{code: string, name_en: string|null, name_km: string|null}> */
    public function options(string $level, ?string $parent = null): array
    {
        $query = AdministrativeArea::query()->where('level', $level);

        if ($level !== 'province') {
            if ($parent === null) {
                return [];
            }

            $query->where($level === 'district' ? 'province_code' : 'district_code', $parent);
        }

        $options = $query->orderBy('code')->get(['code', 'name_en', 'name_km'])
            ->map(fn (AdministrativeArea $area): array => [
                'code' => $area->code,
                'name_en' => $area->name_en,
                'name_km' => $area->name_km,
            ])->all();

        if ($level === 'commune') {
            $unlistedCodes = AdministrativeArea::query()
                ->where('level', 'village')
                ->where('district_code', $parent)
                ->whereNotNull('commune_code')
                ->whereDoesntHave('commune')
                ->orderBy('commune_code')
                ->distinct()
                ->pluck('commune_code');

            foreach ($unlistedCodes as $code) {
                $options[] = ['code' => $code, 'name_en' => null, 'name_km' => null];
            }
        }

        return array_values($options);
    }
}
