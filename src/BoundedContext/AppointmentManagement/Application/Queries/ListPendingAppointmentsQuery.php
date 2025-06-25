<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Queries;

use DateTime;

final class ListPendingAppointmentsQuery
{
    public function __construct(
        public string $centerKey,
        public ?DateTime $startDate = null,
        public ?DateTime $endDate = null
    ) {
        if (empty($centerKey)) {
            throw new \InvalidArgumentException('Center key cannot be empty');
        }

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date cannot be after end date');
        }
    }

    public function centerKey(): string
    {
        return $this->centerKey;
    }

    public function startDate(): DateTime
    {
        return $this->startDate;
    }

    public function endDate(): DateTime
    {
        return $this->endDate;
    }
}
