<?php

namespace App\Http\Requests;

use App\Domain\AdministrativeArea\Models\AdministrativeArea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdministrativeAreaIndexRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'level' => ['sometimes', Rule::in(AdministrativeArea::LEVELS)],
            'q' => ['nullable', 'string', 'max:100'],
            'province_code' => ['nullable', 'string', 'regex:/\A[0-9]{2}\z/'],
            'district_code' => ['nullable', 'string', 'regex:/\A[0-9]{4}\z/'],
            'commune_code' => ['nullable', 'string', 'regex:/\A[0-9]{6}\z/'],
            'lang' => ['sometimes', Rule::in(['en', 'km'])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'between:1,1000000'],
        ];
    }

    /** @return array{level?: string, q?: string|null, province_code?: string|null, district_code?: string|null, commune_code?: string|null, lang?: string, per_page?: int|string, page?: int|string} */
    public function filters(): array
    {
        return $this->validated();
    }
}
