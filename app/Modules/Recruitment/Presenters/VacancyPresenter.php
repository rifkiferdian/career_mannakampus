<?php

namespace App\Modules\Recruitment\Presenters;

class VacancyPresenter
{
    /**
     * @param list<array<string, mixed>>              $vacancies
     * @param array<int, list<array<string, mixed>>> $questionsByVacancy
     *
     * @return list<array<string, mixed>>
     */
    public function presentMany(array $vacancies, array $questionsByVacancy): array
    {
        foreach ($vacancies as &$vacancy) {
            $department = trim((string) ($vacancy['department'] ?? ''));
            $department = $department !== '' ? $department : 'Umum';

            $vacancy['department_slug'] = (string) ($vacancy['department_code'] ?? $this->slugify($department));
            $vacancy['icon_text'] = $this->initials((string) $vacancy['title']);
            $vacancy['icon_class'] = $this->iconClass($department);
            $vacancy['age_requirement'] = $this->ageRequirement($vacancy['minimum_age'] ?? null);
            $vacancy['education_requirement'] = $this->educationRequirement($vacancy['minimum_education'] ?? null);
            $vacancy['screening_questions'] = $questionsByVacancy[(int) $vacancy['id']] ?? [];
        }
        unset($vacancy);

        return $vacancies;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    private function initials(string $title): string
    {
        $words = preg_split('/\s+/', trim($title)) ?: [];
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials !== '' ? $initials : 'MK';
    }

    private function iconClass(string $department): string
    {
        $department = strtolower($department);

        if (str_contains($department, 'product') || str_contains($department, 'technology')) {
            return 'job-icon-product';
        }

        if (str_contains($department, 'marketing')) {
            return 'job-icon-marketing';
        }

        return 'job-icon-people';
    }

    private function ageRequirement(mixed $minimumAge): string
    {
        if ($minimumAge === null || $minimumAge === '') {
            return 'Usia belum ditentukan';
        }

        return 'Min. usia ' . (int) $minimumAge . ' tahun';
    }

    private function educationRequirement(mixed $minimumEducation): string
    {
        $minimumEducation = trim((string) $minimumEducation);

        if ($minimumEducation === '') {
            return 'Pendidikan belum ditentukan';
        }

        return 'Min. pendidikan ' . $minimumEducation;
    }
}
