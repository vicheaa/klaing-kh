<?php

namespace App\Http\Resources;

use App\Domain\AdministrativeArea\Models\AdministrativeArea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AdministrativeArea */
class AdministrativeAreaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'code' => $this->code,
            'name_km' => $this->name_km,
            'name_en' => $this->name_en,
            'province_code' => $this->province_code,
            'district_code' => $this->district_code,
            'commune_code' => $this->commune_code,
            'province' => $this->reference($this->province),
            'district' => $this->reference($this->district),
            'commune' => $this->reference($this->commune),
        ];
    }

    /** @return array{code: string, name_km: string, name_en: string}|null */
    private function reference(?AdministrativeArea $area): ?array
    {
        return $area === null ? null : [
            'code' => $area->code,
            'name_km' => $area->name_km,
            'name_en' => $area->name_en,
        ];
    }
}
