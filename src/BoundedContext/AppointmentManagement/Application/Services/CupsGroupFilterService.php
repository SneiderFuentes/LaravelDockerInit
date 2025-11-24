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
     * Obtiene IDs de citas para SAN02 en un mes específico
     * @param string $centerKey
     * @param string|null $targetDate Fecha objetivo (Y-m-d). Si es null, usa el mes actual.
     */
    public function filterAppointmentsByCupsGroup(string $centerKey, ?string $targetDate = null): array
    {
        $targetMonth = $targetDate ? Carbon::parse($targetDate) : Carbon::now();
        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();

        Log::info('Obteniendo IDs de citas para SAN02', [
            'center_key' => $centerKey,
            'target_date' => $targetDate,
            'month' => $targetMonth->format('Y-m'),
            'start_date' => $startOfMonth->format('Y-m-d'),
            'end_date' => $endOfMonth->format('Y-m-d')
        ]);

        // Obtener solo IDs de citas de SAN02 del mes objetivo
        $appointmentIds = $this->appointmentRepository->findByDateAndEntity(
            $centerKey,
            $startOfMonth,
            $endOfMonth,
            'SAN02'
        );

        Log::info('IDs de citas encontradas para SAN02', [
            'total_appointment_ids' => count($appointmentIds),
            'month' => $targetMonth->format('Y-m')
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

    /**
     * Verifica si un CUPS está en el límite de riesgo compartido para una fecha específica
     * @param string $cupCode Código CUPS a verificar
     * @param string $targetDate Fecha del slot (Y-m-d)
     * @param string $centerKey Clave del centro de datos
     * @return bool True si está en el límite (no se puede agendar), False si hay cupo disponible
     */
    public function isCupAtLimitForDate(string $cupCode, string $targetDate, string $centerKey): bool
    {
        $cupsGroups = config('cups_groups.groups', []);

        // Buscar si el CUPS pertenece a algún grupo
        $groupInfo = null;
        foreach ($cupsGroups as $groupName => $group) {
            if (in_array($cupCode, $group['cups'])) {
                $groupInfo = [
                    'group_name' => $groupName,
                    'cups' => $group['cups'],
                    'max' => $group['max']
                ];
                break;
            }
        }

        // Si el CUPS no pertenece a ningún grupo, no hay límite
        if (!$groupInfo) {
            return false;
        }

        // Obtener IDs de citas del mes de la fecha objetivo
        $appointmentIds = $this->filterAppointmentsByCupsGroup($centerKey, $targetDate);

        // Obtener total de cantidades para este grupo
        $groupTotal = $this->getTotalQuantitiesForGroupCups(
            $appointmentIds,
            $groupInfo['cups'],
            $centerKey
        );

        $isAtLimit = $groupTotal['total_quantity'] >= $groupInfo['max'];

        if ($isAtLimit) {
            $targetMonth = Carbon::parse($targetDate)->format('Y-m');
            Log::info('CUPS at shared risk limit for month', [
                'cup_code' => $cupCode,
                'group_name' => $groupInfo['group_name'],
                'target_date' => $targetDate,
                'target_month' => $targetMonth,
                'current_quantity' => $groupTotal['total_quantity'],
                'max_limit' => $groupInfo['max']
            ]);
        }

        return $isAtLimit;
    }
}
