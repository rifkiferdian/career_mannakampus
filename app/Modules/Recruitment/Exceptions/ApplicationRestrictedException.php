<?php

namespace App\Modules\Recruitment\Exceptions;

use DomainException;

class ApplicationRestrictedException extends DomainException
{
    /** @param array<string, mixed> $restriction */
    public function __construct(
        private readonly array $restriction,
    ) {
        parent::__construct('Lamaran belum dapat diproses karena terdapat pembatasan pendaftaran.');
    }

    /** @return array<string, mixed> */
    public function restriction(): array
    {
        return $this->restriction;
    }
}
