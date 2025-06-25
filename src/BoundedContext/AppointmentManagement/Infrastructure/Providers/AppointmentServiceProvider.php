<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Providers;

use Core\BoundedContext\AppointmentManagement\Application\Commands\CancelAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\Commands\ConfirmAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\Commands\CreateAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\CancelAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ConfirmAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\CreateAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ListPendingAppointmentsHandler;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Adapters\AppointmentRepositoryFactory;
use Illuminate\Support\ServiceProvider;

final class AppointmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AppointmentRepositoryFactory::class);

        $this->app->bind(AppointmentRepositoryInterface::class, function ($app) {
            return $app->make(AppointmentRepositoryFactory::class)->create();
        });

        $this->app->bind(CreateAppointmentHandler::class);
        $this->app->bind(ConfirmAppointmentHandler::class);
        $this->app->bind(CancelAppointmentHandler::class);
        $this->app->bind(ListPendingAppointmentsHandler::class);

        // Repositorios auxiliares con inyección de dependencias y binding por interfaz
        $this->app->singleton(\Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface::class, function ($app) {
            return new \Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\PatientRepository(
                $app->make(\Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService::class)
            );
        });
        $this->app->singleton(\Core\BoundedContext\AppointmentManagement\Domain\Repositories\EntityRepositoryInterface::class, function ($app) {
            return new \Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\EntityRepository(
                $app->make(\Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService::class)
            );
        });
        $this->app->singleton(\Core\BoundedContext\AppointmentManagement\Domain\Repositories\SoatRepositoryInterface::class, function ($app) {
            return new \Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\SoatRepository(
                $app->make(\Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService::class)
            );
        });
        $this->app->singleton(\Core\BoundedContext\AppointmentManagement\Domain\Repositories\ScheduleConfigRepositoryInterface::class, function ($app) {
            return new \Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\ScheduleConfigRepository(
                $app->make(\Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService::class)
            );
        });

        // CupProcedureRepository
        $this->app->singleton(\Core\BoundedContext\AppointmentManagement\Domain\Repositories\CupProcedureRepositoryInterface::class, function ($app) {
            return new \Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\CupProcedureRepository(
                $app->make(\Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService::class)
            );
        });

        // DoctorRepository
        $this->app->singleton(\Core\BoundedContext\AppointmentManagement\Domain\Repositories\DoctorRepositoryInterface::class, function ($app) {
            return new \Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\DoctorRepository(
                $app->make(\Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService::class)
            );
        });

        $this->app->singleton(\Core\BoundedContext\AppointmentManagement\Domain\Repositories\ScheduleRepositoryInterface::class, function ($app) {
            return new \Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\ScheduleRepository(
                $app->make(\Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService::class)
            );
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
    }
}
