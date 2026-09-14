<?php

use App\Modules\Recruitment\Controllers\ApplicationController;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ApplicationPositionSelectionTest extends CIUnitTestCase
{
    public function testApplicantCanSelectTwoEquivalentPositionsAcrossDepartments(): void
    {
        $controller = new ApplicationController();
        $method = new ReflectionMethod($controller, 'selectedVacancies');
        $vacancies = [
            ['id' => 32, 'title' => 'Designer', 'department' => 'Marketing', 'minimum_education' => 'D3/S1'],
            ['id' => 2, 'title' => 'Programmer', 'department' => 'Information Technology', 'minimum_education' => 'D3/S1'],
        ];

        $selected = $method->invoke(
            $controller,
            [32, 2],
            ['32' => 1, '2' => 2],
            $vacancies[0],
            $vacancies,
        );

        $this->assertSame([32, 2], array_column($selected, 'id'));
        $this->assertSame([1, 2], array_column($selected, 'preference_order'));
    }

    public function testThirdPositionIsRejected(): void
    {
        $this->expectException(DomainException::class);
        $this->select(['SMA/SMK', 'SMA/SMK', 'SMA/SMK']);
    }

    public function testSchoolCannotAddDegreePosition(): void
    {
        $this->expectException(DomainException::class);
        $this->select(['SMA/SMK', 'S1']);
    }

    public function testDegreeCannotAddSchoolPosition(): void
    {
        $this->expectException(DomainException::class);
        $this->select(['S1', 'SMA/SMK']);
    }

    public function testPrimaryCannotBeChangedByTamperingWithPriorities(): void
    {
        $this->expectException(DomainException::class);
        $this->select(['S1', 'S1'], [1 => 2, 2 => 1]);
    }

    public function testSchoolAliasesAreEquivalent(): void
    {
        $this->assertCount(2, $this->select(['SMA', 'SMK']));
    }

    public function testCombinedRequirementsIgnoreOrderAndWhitespace(): void
    {
        $this->assertCount(2, $this->select(['D3/S1', ' s1 / d3 ']));
    }

    public function testMissingEducationOnlyAllowsTheOriginalPosition(): void
    {
        $this->assertCount(1, $this->select(['']));
        $this->expectException(DomainException::class);
        $this->select(['', '']);
    }

    public function testExistingApplicationEducationIsUsedAsAnchor(): void
    {
        $policy = new \App\Modules\Recruitment\Services\ApplicationPositionPolicy();
        $this->expectException(DomainException::class);
        $policy->validate([['id' => 2, 'minimum_education' => 'S1']], ['id' => 1, 'minimum_education' => 'SMA/SMK']);
    }

    private function select(array $educations, ?array $priorities = null): array
    {
        $vacancies = [];
        foreach ($educations as $index => $education) {
            $vacancies[] = ['id' => $index + 1, 'minimum_education' => $education];
        }
        $ids = array_column($vacancies, 'id');

        return (new ReflectionMethod(ApplicationController::class, 'selectedVacancies'))->invoke(
            new ApplicationController(), $ids, $priorities ?? array_combine($ids, $ids), $vacancies[0], $vacancies,
        );
    }
}
