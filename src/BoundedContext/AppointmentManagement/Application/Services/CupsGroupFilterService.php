<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Services;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CupsGroupFilterService
{
    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository
    ) {}

    /**
     * Obtiene IDs de citas del mes actual para SAN02
     */
    public function filterAppointmentsByCupsGroup(string $centerKey): array
    {
        $currentMonth = Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        Log::info('Obteniendo IDs de citas para SAN02', [
            'center_key' => $centerKey,
            'month' => $currentMonth->format('Y-m'),
            'start_date' => $startOfMonth->format('Y-m-d'),
            'end_date' => $endOfMonth->format('Y-m-d')
        ]);

        // Obtener solo IDs de citas de SAN02 del mes actual
        $appointmentIds = $this->appointmentRepository->findByDateAndEntity(
            $centerKey,
            $startOfMonth,
            $endOfMonth,
            'SAN02'
        );

        Log::info('IDs de citas encontradas para SAN02', [
            'total_appointment_ids' => count($appointmentIds),
            'month' => $currentMonth->format('Y-m')
        ]);

        return $appointmentIds;
    }

    /**
     * Obtiene el total de cantidades de pxcita para un grupo específico de CUPS
     */
    public function getTotalQuantitiesForGroupCups(array $appointmentIds, array $groupCups, string $centerKey): array
    {
        if (empty($appointmentIds)) {
            return [
                'total_quantity' => 0,
                'is_at_limit' => false
            ];
        }

        Log::info('Obteniendo cantidades de pxcita para grupo específico', [
            'center_key' => $centerKey,
            'appointment_ids_count' => count($appointmentIds),
            'group_cups' => $groupCups
        ]);

        // Usar el método del repositorio para sumar cantidades
        $totalQuantity = $this->appointmentRepository->sumQuantitiesByAppointmentIdsAndCups(
            $appointmentIds,
            $groupCups,
            $centerKey
        );

        Log::info('Total de cantidades obtenido para grupo específico', [
            'total_quantity' => $totalQuantity,
            'center_key' => $centerKey,
            'group_cups' => $groupCups
        ]);

        return [
            'total_quantity' => (int) $totalQuantity,
            'is_at_limit' => false // Se calculará en el Job con el maxLimit
        ];
    }
}
