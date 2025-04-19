<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Application\Commands\CancelAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\Commands\ConfirmAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\CancelAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ConfirmAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ListPendingAppointmentsHandler;
use Core\BoundedContext\AppointmentManagement\Application\Queries\ListPendingAppointmentsQuery;
use Core\BoundedContext\AppointmentManagement\Domain\Exceptions\AppointmentNotFoundException;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AppointmentController extends Controller
{
    public function __construct(
        private ListPendingAppointmentsHandler $listPendingAppointmentsHandler,
        private ConfirmAppointmentHandler $confirmAppointmentHandler,
        private CancelAppointmentHandler $cancelAppointmentHandler
    ) {}

    public function index(Request $request, string $centerKey): JsonResponse
    {
        try {
            $startDate = new DateTime($request->get('start_date', 'now'));
            $endDate = new DateTime($request->get('end_date', '+1 day'));

            $query = new ListPendingAppointmentsQuery(
                $centerKey,
                $startDate,
                $endDate
            );

            $appointments = $this->listPendingAppointmentsHandler->handle($query);

            return new JsonResponse([
                'data' => array_map(fn($dto) => $dto->toArray(), $appointments),
                'meta' => [
                    'total' => count($appointments),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error listing pending appointments: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function confirm(string $centerKey, string $id): JsonResponse
    {
        try {
            $command = new ConfirmAppointmentCommand($id, $centerKey);
            $appointment = $this->confirmAppointmentHandler->handle($command);

            return new JsonResponse([
                'data' => $appointment->toArray(),
                'message' => 'Appointment confirmed successfully',
            ]);
        } catch (AppointmentNotFoundException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error confirming appointment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, string $centerKey, string $id): JsonResponse
    {
        try {
            $reason = $request->get('reason');
            $command = new CancelAppointmentCommand($id, $centerKey, $reason);
            $appointment = $this->cancelAppointmentHandler->handle($command);

            return new JsonResponse([
                'data' => $appointment->toArray(),
                'message' => 'Appointment cancelled successfully',
            ]);
        } catch (AppointmentNotFoundException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error cancelling appointment: ' . $e->getMessage(),
            ], 500);
        }
    }
}
