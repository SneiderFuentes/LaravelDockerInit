<?php

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Adapters;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\GenericDbAppointmentRepository;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\ScheduleConfigRepository;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;

class AppointmentRepositoryFactory
{
    public function __construct(
        private GetSubaccountConfigService $subaccountConfigService,
        private ScheduleConfigRepository $scheduleConfigRepository
    ) {}

    public function create(): AppointmentRepositoryInterface
    {
        return new GenericDbAppointmentRepository(
            $this->subaccountConfigService,
            $this->scheduleConfigRepository
        );
    }
}
