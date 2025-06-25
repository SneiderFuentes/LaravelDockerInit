<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Queries;

final class GetAppointmentQuery
{
    public function __construct(
        public readonly string $id,
        public readonly string $centerKey
    ) {}
}
