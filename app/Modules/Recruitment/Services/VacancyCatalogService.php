<?php

namespace App\Modules\Recruitment\Services;

use App\Modules\Recruitment\Models\ScreeningQuestionModel;
use App\Modules\Recruitment\Models\DepartmentModel;
use App\Modules\Recruitment\Models\VacancyModel;
use App\Modules\Recruitment\Presenters\VacancyPresenter;
use DateTimeImmutable;

class VacancyCatalogService
{
    public function __construct(
        private readonly VacancyModel $vacancyModel,
        private readonly ScreeningQuestionModel $questionModel,
        private readonly DepartmentModel $departmentModel,
        private readonly VacancyPresenter $presenter,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function openVacancies(?int $limit = null): array
    {
        return $this->searchOpenVacancies('', '', $limit);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function openVacancyByCode(string $code): ?array
    {
        foreach ($this->openVacancies() as $vacancy) {
            if (hash_equals((string) $vacancy['code'], $code)) {
                return $vacancy;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function compatibleVacancies(string $code): array
    {
        $selectedVacancy = $this->openVacancyByCode($code);
        if ($selectedVacancy === null) {
            return [];
        }

        return array_values(array_filter(
            $this->openVacancies(),
            static fn (array $vacancy): bool =>
                (int) $vacancy['requirement_group_id'] === (int) $selectedVacancy['requirement_group_id'],
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchOpenVacancies(
        string $keyword = '',
        string $departmentCode = '',
        ?int $limit = null,
    ): array {
        $vacancies = $this->vacancyModel->findPublicOpen(
            new DateTimeImmutable(),
            $limit,
            trim($keyword),
            trim($departmentCode),
        );

        if ($vacancies === []) {
            return [];
        }

        $questions = $this->questionModel->findForVacancies(
            array_map('intval', array_column($vacancies, 'id')),
        );
        $questionsByVacancy = [];

        foreach ($questions as $question) {
            $questionsByVacancy[(int) $question['vacancy_id']][] = $question;
        }

        return $this->presenter->presentMany($vacancies, $questionsByVacancy);
    }

    /**
     * @return array{
     *     vacancies: list<array<string, mixed>>,
     *     departments: array<string, string>
     * }
     */
    public function catalogPageData(): array
    {
        $vacancies = $this->openVacancies();
        $departments = [];

        foreach ($this->departmentModel->findWithOpenVacancies(new DateTimeImmutable()) as $department) {
            $departments[$department['code']] = $department['name'];
        }

        return [
            'vacancies'   => $vacancies,
            'departments' => $departments,
        ];
    }
}
