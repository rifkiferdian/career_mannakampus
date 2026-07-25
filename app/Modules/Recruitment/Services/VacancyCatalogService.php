<?php

namespace App\Modules\Recruitment\Services;

use App\Modules\Recruitment\Models\ScreeningQuestionModel;
use App\Modules\Recruitment\Models\VacancyModel;
use App\Modules\Recruitment\Presenters\VacancyPresenter;
use DateTimeImmutable;

class VacancyCatalogService
{
    public function __construct(
        private readonly VacancyModel $vacancyModel,
        private readonly ScreeningQuestionModel $questionModel,
        private readonly VacancyPresenter $presenter,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function openVacancies(?int $limit = null): array
    {
        $vacancies = $this->vacancyModel->findPublicOpen(new DateTimeImmutable(), $limit);

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

        foreach ($vacancies as $vacancy) {
            $departments[$vacancy['department_slug']] = $vacancy['department'] ?: 'Umum';
        }

        asort($departments);

        return [
            'vacancies'   => $vacancies,
            'departments' => $departments,
        ];
    }
}
