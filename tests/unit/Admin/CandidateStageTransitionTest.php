<?php

namespace Tests\Unit\Admin;

use App\Modules\Admin\Controllers\CandidateController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CandidateStageTransitionTest extends TestCase
{
    /** @var array<int, list<array<string, mixed>>> */
    private array $templateStages;

    /** @var list<array<string, mixed>> */
    private array $allStages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateStages = [
            1 => [
                ['id' => 1, 'code' => 'document_screening', 'name' => 'Screening Berkas', 'display_order' => 1],
                ['id' => 2, 'code' => 'written_test', 'name' => 'Tes Tertulis', 'display_order' => 2, 'is_schedulable' => 1],
                ['id' => 3, 'code' => 'accepted', 'name' => 'Diterima', 'display_order' => 3],
            ],
        ];
        $this->allStages = [
            ...$this->templateStages[1],
            ['id' => 4, 'code' => 'rejected', 'name' => 'Ditolak', 'display_order' => 99],
        ];
    }

    public function testNewApplicationGoesDirectlyToScreeningDecision(): void
    {
        $stages = $this->nextStages('lamaran_baru');

        $this->assertSame(['screening_passed', 'screening_failed'], array_column($stages, 'code'));
        $this->assertSame(['Lolos Screening', 'Tidak Lolos Screening'], array_column($stages, 'name'));
        $this->assertNotContains('document_screening', array_column($stages, 'code'));
    }

    public function testLegacyInProgressScreeningCanStillBeDecided(): void
    {
        $stages = $this->nextStages('document_screening');

        $this->assertSame(['screening_passed', 'screening_failed'], array_column($stages, 'code'));
    }

    public function testPassedScreeningContinuesToWrittenTest(): void
    {
        $stages = $this->nextStages('screening_passed');

        $this->assertSame(['written_test', 'rejected'], array_column($stages, 'code'));
    }

    /** @return list<array<string, mixed>> */
    private function nextStages(string $status): array
    {
        $method = new ReflectionMethod(CandidateController::class, 'nextStages');
        $method->setAccessible(true);

        /** @var list<array<string, mixed>> $result */
        $result = $method->invoke(
            new CandidateController(),
            1,
            $status,
            $this->templateStages,
            $this->allStages,
        );

        return $result;
    }
}
