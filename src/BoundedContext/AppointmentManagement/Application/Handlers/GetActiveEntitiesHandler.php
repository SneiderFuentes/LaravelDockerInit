<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\EntityRepositoryInterface;

class GetActiveEntitiesHandler
{
    public function __construct(private EntityRepositoryInterface $entityRepository) {}

    public function handle(): array
    {
        return $this->entityRepository->findAllActive();
    }
}
