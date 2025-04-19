<?php

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Adapters;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\GenericDbAppointmentRepository;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;

class AppointmentRepositoryFactory
{
    private GetSubaccountConfigService $subaccountConfigService;

    public function __construct(GetSubaccountConfigService $subaccountConfigService)
    {
        $this->subaccountConfigService = $subaccountConfigService;
    }

    public function create(): AppointmentRepositoryInterface
    {
        return new GenericDbAppointmentRepository($this->subaccountConfigService);
    }
}
