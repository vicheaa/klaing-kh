<?php

namespace App\Domain\AdministrativeArea\Actions;

use App\Domain\AdministrativeArea\Models\AdministrativeArea;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class ImportAdministrativeAreas
{
    private const array FILES = [
        'province' => 'CambodiaProvinceList2025.csv',
        'district' => 'CambodiaDistrictList2025.csv',
        'commune' => 'CambodiaCommuneList2025.csv',
        'village' => 'CambodiaVillagesList2025.csv',
    ];

    private const array CODE_PATTERNS = [
        'province_code' => '/\A[0-9]{2}\z/',
        'district_code' => '/\A[0-9]{4}\z/',
        'commune_code' => '/\A[0-9]{6}\z/',
        'village_code' => '/\A[0-9]{7}[0-9-]\z/',
    ];

    public function handle(string $directory): void
    {
        DB::transaction(function () use ($directory): void {
            $codeColumns = [];
            $provinces = [];
            $districts = [];
            $communes = [];

            foreach (self::FILES as $level => $filename) {
                $codeColumns[] = $level.'_code';
                $columns = [...$codeColumns, $level.'_kh', $level.'_en'];
                $file = new SplFileObject($directory.DIRECTORY_SEPARATOR.$filename);
                $file->setCsvControl(',', '"', '');
                $header = $file->fgetcsv();

                if ($header === false || $header === [null]) {
                    throw new RuntimeException("Missing column headers in {$filename}.");
                }

                $header[0] = str_replace("\xEF\xBB\xBF", '', $header[0]);
                if ($header !== $columns) {
                    throw new RuntimeException("Unexpected column headers in {$filename}.");
                }

                $seen = [];
                $batch = [];
                while (($values = $file->fgetcsv()) !== false) {
                    if ($values === [null]) {
                        continue;
                    }

                    $line = $file->key() + 1;
                    if (count($values) !== count($columns)) {
                        throw new RuntimeException("Unexpected column count in {$filename}:{$line}.");
                    }

                    $row = array_combine($columns, array_map(
                        static fn (?string $value): string => trim($value ?? ''),
                        $values,
                    ));
                    foreach ($codeColumns as $column) {
                        if (preg_match(self::CODE_PATTERNS[$column], $row[$column]) !== 1) {
                            throw new RuntimeException("Invalid {$column} in {$filename}:{$line}.");
                        }
                    }

                    $code = $row[$level.'_code'];
                    $nameKm = $row[$level.'_kh'];
                    $nameEn = $row[$level.'_en'];
                    if ($nameKm === '' || $nameEn === '' || ! mb_check_encoding([$nameKm, $nameEn], 'UTF-8') || mb_strlen($nameKm) > 255 || mb_strlen($nameEn) > 255) {
                        throw new RuntimeException("Invalid place name in {$filename}:{$line}.");
                    }

                    $province = $level === 'province' ? null : $row['province_code'];
                    $district = in_array($level, ['commune', 'village'], true) ? $row['district_code'] : null;
                    $commune = $level === 'village' ? $row['commune_code'] : null;
                    if (($province !== null && ! isset($provinces[$province]))
                        || ($district !== null && ($districts[$district] ?? null) !== $province)
                        || ($commune !== null && isset($communes[$commune]) && $communes[$commune] !== $district)) {
                        throw new RuntimeException("Inconsistent parent codes in {$filename}:{$line}.");
                    }

                    // Village codes repeat, including within a single commune.
                    $id = $commune === null ? $code : hash('sha256', "{$commune}:{$code}:{$nameKm}");
                    if (isset($seen[$id])) {
                        throw new RuntimeException("Duplicate area in {$filename}:{$line}.");
                    }
                    $seen[$id] = true;

                    match ($level) {
                        'province' => $provinces[$code] = true,
                        'district' => $districts[$code] = $province,
                        'commune' => $communes[$code] = $district,
                        default => null,
                    };

                    $batch[] = [
                        'id' => $id,
                        'level' => $level,
                        'code' => $code,
                        'name_km' => $nameKm,
                        'name_en' => $nameEn,
                        'province_code' => $province,
                        'district_code' => $district,
                        'commune_code' => $commune,
                    ];

                    if (count($batch) === 100) {
                        $this->upsert($batch);
                        $batch = [];
                    }
                }

                if ($seen === []) {
                    throw new RuntimeException("No area records in {$filename}.");
                }

                $this->upsert($batch);
            }
        }, 3);
    }

    /** @param list<array<string, string|null>> $batch */
    private function upsert(array $batch): void
    {
        AdministrativeArea::query()->upsert($batch, ['id'], [
            'name_km', 'name_en', 'province_code', 'district_code', 'commune_code',
        ]);
    }
}
