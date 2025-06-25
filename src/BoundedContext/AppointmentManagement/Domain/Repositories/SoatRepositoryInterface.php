<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Repositories;

interface SoatRepositoryInterface
{
    public function findPrice(string $cupCode, string $tipoPrecio): ?float;
}
