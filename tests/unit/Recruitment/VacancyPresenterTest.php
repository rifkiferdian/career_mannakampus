<?php

use App\Modules\Recruitment\Presenters\VacancyPresenter;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class VacancyPresenterTest extends CIUnitTestCase
{
    public function testItPreparesVacancyForTheView(): void
    {
        $presenter = new VacancyPresenter();
        $questions = [
            10 => [
                ['question_text' => 'Bersedia bekerja shift?'],
            ],
        ];

        $result = $presenter->presentMany([
            [
                'id'         => 10,
                'title'      => 'Content Marketing Specialist',
                'department' => 'Digital Marketing',
                'minimum_age' => 18,
                'minimum_education' => 'D3/S1',
            ],
        ], $questions);

        $this->assertSame('digital-marketing', $result[0]['department_slug']);
        $this->assertSame('CM', $result[0]['icon_text']);
        $this->assertSame('job-icon-marketing', $result[0]['icon_class']);
        $this->assertSame('Min. usia 18 tahun', $result[0]['age_requirement']);
        $this->assertSame('Min. pendidikan D3/S1', $result[0]['education_requirement']);
        $this->assertSame($questions[10], $result[0]['screening_questions']);
    }

    public function testItUsesSafeDefaultsForEmptyDepartmentAndTitle(): void
    {
        $presenter = new VacancyPresenter();
        $result = $presenter->presentMany([
            [
                'id'         => 11,
                'title'      => '',
                'department' => null,
            ],
        ], []);

        $this->assertSame('umum', $result[0]['department_slug']);
        $this->assertSame('MK', $result[0]['icon_text']);
        $this->assertSame('job-icon-people', $result[0]['icon_class']);
        $this->assertSame('Usia belum ditentukan', $result[0]['age_requirement']);
        $this->assertSame('Pendidikan belum ditentukan', $result[0]['education_requirement']);
        $this->assertSame([], $result[0]['screening_questions']);
    }
}
