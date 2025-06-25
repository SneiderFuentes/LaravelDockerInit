<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Queries;

use DateTime;

final class ListAppointmentsQuery
{
    public function __construct(
        public readonly string $centerKey,
        public readonly ?DateTime $startDate = null,
        public readonly ?DateTime $endDate = null
    ) {}
}
