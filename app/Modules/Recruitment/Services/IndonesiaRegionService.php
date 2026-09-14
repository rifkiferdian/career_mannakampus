<?php

namespace App\Modules\Recruitment\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

class IndonesiaRegionService
{
    private const PARENTS = ['provinces' => null, 'regencies' => 'province_code', 'districts' => 'regency_code', 'villages' => 'district_code'];

    public function __construct(private readonly BaseConnection $database)
    {
    }

    public function options(string $level, string $parent = ''): array
    {
        if (! array_key_exists($level, self::PARENTS)) {
            throw new DomainException('Jenis wilayah tidak valid.');
        }
        $builder = $this->database->table($level)->select('code, name');
        if (self::PARENTS[$level] !== null) {
            if ($parent === '' || strlen($parent) > 20) {
                return [];
            }
            $builder->where(self::PARENTS[$level], $parent);
        }

        return $builder->orderBy('name')->get()->getResultArray();
    }

    public function resolve(array $input): array
    {
        $builder = $this->database->table('view_indonesia_regions');
        foreach (['province_code', 'regency_code', 'district_code', 'village_code'] as $field) {
            $value = $input[$field] ?? null;
            if (! is_string($value) || $value === '' || strlen($value) > 20) {
                throw new DomainException('Pilih provinsi, kabupaten/kota, kecamatan, dan kelurahan/desa domisili.');
            }
            $builder->where($field, $value);
        }
        $region = $builder->get()->getRowArray();
        if ($region === null) {
            throw new DomainException('Pilihan wilayah domisili tidak sesuai. Silakan pilih kembali wilayah secara berurutan.');
        }

        return $region;
    }
}
