<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Infrastructure\Providers;

use Core\BoundedContext\SubaccountManagement\Domain\Repositories\SubaccountRepositoryInterface;
use Core\BoundedContext\SubaccountManagement\Infrastructure\Factories\SubaccountFactory;
use Core\BoundedContext\SubaccountManagement\Infrastructure\Persistence\Eloquent\EloquentSubaccountRepository;
use Illuminate\Support\ServiceProvider;

final class SubaccountServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SubaccountRepositoryInterface::class,
            EloquentSubaccountRepository::class
        );

        $this->app->singleton(SubaccountFactory::class, function ($app) {
            return new SubaccountFactory(
                $app->make(SubaccountRepositoryInterface::class)
            );
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
    }
}
