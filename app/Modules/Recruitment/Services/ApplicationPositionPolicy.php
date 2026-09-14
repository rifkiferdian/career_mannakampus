<?php

namespace App\Modules\Recruitment\Services;

use DomainException;

final class ApplicationPositionPolicy
{
    public const MAX_POSITIONS = 2;

    public function equivalent(array $first, array $second): bool
    {
        if (isset($first['id'], $second['id']) && (int) $first['id'] === (int) $second['id']) {
            return true;
        }

        $education = $this->educationKey((string) ($first['minimum_education'] ?? ''));

        return $education !== '' && $education === $this->educationKey((string) ($second['minimum_education'] ?? ''));
    }

    public function validate(array $vacancies, ?array $primary = null): void
    {
        if ($vacancies === [] || count($vacancies) > self::MAX_POSITIONS) {
            throw new DomainException('Pilih minimal satu dan maksimal dua posisi.');
        }

        $primary ??= $vacancies[0];
        foreach ($vacancies as $vacancy) {
            if (! $this->equivalent($primary, $vacancy)) {
                throw new DomainException('Posisi tambahan harus memiliki persyaratan pendidikan yang setara dengan lowongan awal. Lowongan SMA/SMK tidak dapat digabung dengan lowongan S1 atau jenjang lainnya.');
            }
        }
    }

    private function educationKey(string $education): string
    {
        $education = strtoupper(trim($education));
        $education = preg_replace('/\s+/', '', $education) ?? '';
        $levels = preg_split('/[\/,;|&]+/', $education, -1, PREG_SPLIT_NO_EMPTY);
        $levels = array_values(array_unique(array_map(
            static fn (string $level): string => in_array($level, ['SMA', 'SMK'], true) ? 'SMA-SMK' : $level,
            $levels ?: [],
        )));
        sort($levels);

        return implode('/', $levels);
    }
}
