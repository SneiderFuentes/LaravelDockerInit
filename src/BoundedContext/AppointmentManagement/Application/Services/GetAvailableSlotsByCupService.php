<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Services;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\DoctorRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\ScheduleRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\AvailableSlotDTO;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\CupProcedureRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\ScheduleConfigRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;

class GetAvailableSlotsByCupService
{
    public function __construct(
        private DoctorRepositoryInterface $doctorRepository,
        private ScheduleRepositoryInterface $scheduleRepository,
        private CupProcedureRepositoryInterface $cupProcedureRepository,
        private ScheduleConfigRepositoryInterface $scheduleConfigRepository,
        private AppointmentRepositoryInterface $appointmentRepository
    ) {}

    /**
     * @param string $cupId
     * @param int $espacios
     * @return AvailableSlotDTO[]
     */
    public function execute(array $procedures, int $espacios): array
    {
        if (empty($procedures) || empty($procedures[0]['cups'])) {
            return [];
        }
        $cupId = $procedures[0]['cups'];

        $cupInfo = $this->cupProcedureRepository->findByCode($cupId);
        if (!$cupInfo) {
            return [];
        }

        $doctores = $this->doctorRepository->findDoctorsByCupId($cupInfo['id']);

        $doctorDocuments = array_column($doctores, 'doctor_document');
        $doctorMap = [];
        foreach ($doctores as $doctor) {
            $doctorMap[$doctor['doctor_document']] = $doctor['doctor_full_name'] ?? $doctor['doctor_document'];
        }
        // Unir todos los días laborales futuros de todos los doctores

        $daysAllDoctors = $this->scheduleRepository->findFutureWorkingDaysByDoctors($doctorDocuments);
        $slots = [];
        $scheduleCache = [];
        $scheduleConfigCache = [];
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ($daysAllDoctors as $day) {
            if (count($slots) >= 5) break;

            $doctorDocument = $day['doctor_document'];
            $doctorFullName = $doctorMap[$doctorDocument] ?? $doctorDocument;

            if (isset($scheduleCache[$day['agenda_id']])) {
                $schedule = $scheduleCache[$day['agenda_id']];
            } else {
                $schedule = $this->scheduleRepository->findByScheduleId($day['agenda_id'], $cupInfo['type']);
                $scheduleCache[$day['agenda_id']] = $schedule;
            }
            if (!$schedule) continue;
            // Cache de configuración de agenda
            if (isset($scheduleConfigCache[$schedule['id']])) {
                $scheduleConfig = $scheduleConfigCache[$schedule['id']];
            } else {
                $scheduleConfig = $this->scheduleConfigRepository->findByScheduleId($schedule['id']);
                $scheduleConfigCache[$schedule['id']] = $scheduleConfig;
            }
            if (!$scheduleConfig) continue;

            $assignedAppointments = $this->appointmentRepository->findByAgendaAndDate($day['agenda_id'], $day['date']);
            $weekday = (int)date('w', strtotime($day['date']));
            $morningStartKey = 'morning_start_' . $days[$weekday];
            $morningEndKey = 'morning_end_' . $days[$weekday];
            $afternoonStartKey = 'afternoon_start_' . $days[$weekday];
            $afternoonEndKey = 'afternoon_end_' . $days[$weekday];

            $workHours = [];
            if ($day['morning_enabled'] == -1 && !empty($scheduleConfig[$morningStartKey]) && !empty($scheduleConfig[$morningEndKey])) {
                $workHours[] = [
                    'start' => $this->formatHour($scheduleConfig[$morningStartKey]),
                    'end' => $this->formatHour($scheduleConfig[$morningEndKey]),
                ];
            }
            if ($day['afternoon_enabled'] == -1 && !empty($scheduleConfig[$afternoonStartKey]) && !empty($scheduleConfig[$afternoonEndKey])) {
                $workHours[] = [
                    'start' => $this->formatHour($scheduleConfig[$afternoonStartKey]),
                    'end' => $this->formatHour($scheduleConfig[$afternoonEndKey]),
                ];
            }

            foreach ($workHours as $period) {
                $start = new \DateTime($day['date'] . ' ' . $period['start']);
                $end = new \DateTime($day['date'] . ' ' . $period['end']);
                $duration = (int)($scheduleConfig['appointment_duration'] ?? 30);

                $assignedTimes = array_column($assignedAppointments, 'time_slot');
                $availableSlots = [];
                while ($start < $end) {
                    $slotStart = clone $start;
                    $slotEnd = (clone $start)->modify("+{$duration} minutes");
                    if ($slotEnd > $end) break;
                    $slotStartStr = $slotStart->format('H:i');
                    if (!in_array($slotStartStr, $assignedTimes)) {
                        $availableSlots[] = [
                            'start' => $slotStartStr,
                            'end' => $slotEnd->format('H:i'),
                        ];
                    }
                    $start->modify("+{$duration} minutes");
                }

                if ($espacios > 1) {
                    $slotMinutes = array_map(function ($slot) {
                        [$h, $m] = explode(':', $slot['start']);
                        return (int)$h * 60 + (int)$m;
                    }, $availableSlots);

                    for ($i = 0; $i <= count($availableSlots) - $espacios; $i++) {
                        $consecutivos = true;
                        for ($j = 1; $j < $espacios; $j++) {
                            if ($slotMinutes[$i + $j] - $slotMinutes[$i + $j - 1] !== $duration) {
                                $consecutivos = false;
                                break;
                            }
                        }
                        if ($consecutivos) {
                            $slots[] = new AvailableSlotDTO(
                                $day['agenda_id'],
                                $doctorDocument,
                                $doctorFullName,
                                $day['date'],
                                $availableSlots[$i]['start'],
                                $duration * $espacios
                            );
                            if (count($slots) >= 5) break 2;
                        }
                    }
                } else {
                    foreach ($availableSlots as $slot) {
                        $slots[] = new AvailableSlotDTO(
                            $day['agenda_id'],
                            $doctorDocument,
                            $doctorFullName,
                            $day['date'],
                            $slot['start'],
                            $duration
                        );
                        if (count($slots) >= 5) break 2;
                    }
                }
            }
        }
        return $slots;
    }

    private function formatHour(string $value): string
    {
        // Si el valor es tipo '1899-12-30 07:00:00', extrae la hora
        if (preg_match('/\d{2}:\d{2}:\d{2}$/', $value)) {
            return substr($value, 11, 5); // '07:00'
        }
        // Si ya viene como '07:00', lo retorna igual
        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value;
        }
        return $value;
    }
}
