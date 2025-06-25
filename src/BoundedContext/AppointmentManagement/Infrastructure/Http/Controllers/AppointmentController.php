<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Application\Commands\CancelAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\Commands\ConfirmAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\Commands\CreateAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\CancelAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ConfirmAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\CreateAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ListAppointmentsHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\GetAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Queries\ListAppointmentsQuery;
use Core\BoundedContext\AppointmentManagement\Application\Queries\GetAppointmentQuery;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ListPendingAppointmentsHandler;
use Core\BoundedContext\AppointmentManagement\Application\Queries\ListPendingAppointmentsQuery;
use Core\BoundedContext\AppointmentManagement\Domain\Exceptions\AppointmentNotFoundException;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\ConfirmationChannelType;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\EntityRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\SoatRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\ScheduleConfigRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\GenericDbAppointmentRepository;

final class AppointmentController extends Controller
{
    public function __construct(
        private ListAppointmentsHandler $listAppointmentsHandler,
        private GetAppointmentHandler $getAppointmentHandler,
        private ConfirmAppointmentHandler $confirmAppointmentHandler,
        private CancelAppointmentHandler $cancelAppointmentHandler,
        private ListPendingAppointmentsHandler $listPendingAppointmentsHandler,
        private CreateAppointmentHandler $createAppointmentHandler
    ) {}

    public function index(Request $request, $centerKey): JsonResponse
    {
        try {
            $startDate = $request->get('start_date') ? new DateTime($request->get('start_date')) : null;
            $endDate = $request->get('end_date') ? new DateTime($request->get('end_date')) : null;

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
                    'start_date' => $startDate?->format('Y-m-d') ?? 'tomorrow',
                    'end_date' => $endDate?->format('Y-m-d') ?? 'tomorrow 23:59:59',
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error listing pending appointments: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $centerKey, $id): JsonResponse
    {
        try {
            $query = new GetAppointmentQuery($id, $centerKey);
            $appointment = $this->getAppointmentHandler->handle($query);
            return new JsonResponse(['data' => $appointment->toArray()]);
        } catch (AppointmentNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error fetching appointment: ' . $e->getMessage()], 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $command = new CreateAppointmentCommand(
                $request->input('patient_id'),
                $request->input('doctor_id'),
                (int)$request->input('agenda_id'),
                $request->input('date'),
                $request->input('time'),
                $request->input('cups'),
                (int)$request->input('espacios', 1)
            );
            $result = $this->createAppointmentHandler->handle($command);
            return new JsonResponse([
                'data' => $result,
                'message' => 'Appointment(s) created successfully',
                'espacios' => $command->espacios,
                'citas_creadas' => count($result)
            ], 201);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error creating appointment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function confirm(Request $request, string $centerKey, string $id): JsonResponse
    {
        try {
            $channelType = $request->input('channel_type');
            if ($channelType && !in_array($channelType, ['whatsapp', 'voz'])) {
                throw new InvalidArgumentException('Invalid channel type. Must be whatsapp or voz');
            }

            $command = new ConfirmAppointmentCommand(
                $id,
                $centerKey,
                $request->input('channel_id'),
                $channelType ? ConfirmationChannelType::from($channelType) : null
            );

            $appointment = $this->confirmAppointmentHandler->handle($command);

            return new JsonResponse([
                'data' => $appointment->toArray(),
                'message' => 'Appointment confirmed successfully',
            ]);
        } catch (AppointmentNotFoundException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 404);
        } catch (InvalidArgumentException $e) {
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
            $channelType = $request->input('channel_type');
            if ($channelType && !in_array($channelType, ['whatsapp', 'voz'])) {
                throw new InvalidArgumentException('Invalid channel type. Must be whatsapp or voz');
            }

            $command = new CancelAppointmentCommand(
                $id,
                $centerKey,
                $request->input('reason', 'No reason provided'),
                $request->input('channel_id'),
                $channelType ? ConfirmationChannelType::from($channelType) : null
            );

            $appointment = $this->cancelAppointmentHandler->handle($command);

            return new JsonResponse([
                'data' => $appointment->toArray(),
                'message' => 'Appointment cancelled successfully',
            ]);
        } catch (AppointmentNotFoundException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 404);
        } catch (InvalidArgumentException $e) {
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
