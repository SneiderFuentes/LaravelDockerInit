<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Factories;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\GenericDbAppointmentRepository;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;

final class AppointmentRepositoryFactory
{
    public function __construct(
        private GetSubaccountConfigService $configService
    ) {}

    public function create(): AppointmentRepositoryInterface
    {
        // Por ahora solo tenemos una implementación, pero en el futuro
        // podrían existir implementaciones específicas para ciertos centros
        return new GenericDbAppointmentRepository($this->configService);
    }
}
