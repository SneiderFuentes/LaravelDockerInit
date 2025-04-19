<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Application\Services;

use Core\BoundedContext\SubaccountManagement\Application\Queries\GetSubaccountByKeyQuery;
use Core\BoundedContext\SubaccountManagement\Application\Handlers\GetSubaccountByKeyHandler;
use Core\BoundedContext\SubaccountManagement\Domain\ValueObjects\SubaccountConfig;
use Core\BoundedContext\SubaccountManagement\Domain\Exceptions\SubaccountNotFoundException;

final class GetSubaccountConfigService
{
    public function __construct(
        private GetSubaccountByKeyHandler $handler
    ) {}

    public function execute(string $centerKey): SubaccountConfig
    {
        try {
            $query = new GetSubaccountByKeyQuery($centerKey);
            $dto = $this->handler->handle($query);

            return SubaccountConfig::fromArray($dto->config());
        } catch (SubaccountNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Error getting subaccount configuration: ' . $e->getMessage(), 0, $e);
        }
    }
}
