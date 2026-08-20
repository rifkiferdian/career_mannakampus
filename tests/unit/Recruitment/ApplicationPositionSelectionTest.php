<?php

use App\Modules\Recruitment\Controllers\ApplicationController;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ApplicationPositionSelectionTest extends CIUnitTestCase
{
    public function testApplicantCanSelectThreeActivePositionsAcrossDepartments(): void
    {
        $controller = new ApplicationController();
        $method = new ReflectionMethod($controller, 'selectedVacancies');
        $vacancies = [
            ['id' => 32, 'title' => 'Security', 'department' => 'Operation'],
            ['id' => 5, 'title' => 'Pramuniaga', 'department' => 'Operation'],
            ['id' => 2, 'title' => 'Programmer', 'department' => 'Information Technology'],
        ];

        $selected = $method->invoke(
            $controller,
            [32, 5, 2],
            ['32' => 1, '5' => 2, '2' => 3],
            $vacancies[0],
            $vacancies,
        );

        $this->assertSame([32, 5, 2], array_column($selected, 'id'));
        $this->assertSame([1, 2, 3], array_column($selected, 'preference_order'));
    }
}
