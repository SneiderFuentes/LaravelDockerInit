<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Infrastructure\Factories;

use Core\BoundedContext\SubaccountManagement\Domain\Entities\Subaccount;
use Core\BoundedContext\SubaccountManagement\Domain\Repositories\SubaccountRepositoryInterface;
use Core\BoundedContext\SubaccountManagement\Domain\ValueObjects\SubaccountConfig;
use Illuminate\Support\Facades\Config;

final class SubaccountFactory
{
    public function __construct(
        private SubaccountRepositoryInterface $repository
    ) {}

    public function seedFromConfig(): void
    {
        $centers = Config::get('subaccounts.centers', []);

        foreach ($centers as $key => $config) {
            if (empty($key) || empty($config['name']) || empty($config['connection']) || empty($config['tables'])) {
                continue;
            }

            $subaccount = Subaccount::create(
                $key,
                $config['name'],
                SubaccountConfig::fromArray([
                    'key' => $key,
                    'name' => $config['name'],
                    'connection' => $config['connection'],
                    'tables' => $config['tables'],
                    'connections' => $config['connections'] ?? [],
                    'api_header' => $config['api_header'] ?? null,
                    'api_key' => $config['api_key'] ?? null,
                ])
            );

            $this->repository->save($subaccount);
        }
    }
}
