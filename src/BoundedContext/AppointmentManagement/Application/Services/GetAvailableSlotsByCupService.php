<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Services;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\DoctorRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\ScheduleRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\AvailableSlotDTO;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\CupProcedureRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\ScheduleConfigRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Application\Services\CupsGroupFilterService;
use Illuminate\Support\Facades\Log;

class GetAvailableSlotsByCupService
{
    public function __construct(
        private DoctorRepositoryInterface $doctorRepository,
        private ScheduleRepositoryInterface $scheduleRepository,
        private CupProcedureRepositoryInterface $cupProcedureRepository,
        private ScheduleConfigRepositoryInterface $scheduleConfigRepository,
        private AppointmentRepositoryInterface $appointmentRepository,
        private PatientRepositoryInterface $patientRepository,
        private CupsGroupFilterService $cupsGroupFilterService
    ) {}

    /**
     * Mapeo de CUPS que requieren buscar el médico de una cita previa.
     * Formato: 'CUPS_procedimiento' => ['CUPS_consulta1', 'CUPS_consulta2', ...]
     *
     * El sistema buscará la última cita del paciente con alguno de los CUPS del array
     * y filtrará los slots para mostrar solo agendas del médico que lo atendió.
     */
    private const CUPS_REQUIRES_PREVIOUS_DOCTOR = [
        '053105' => ['890374', '890274'], // Bloqueo unión mioneural → requiere consulta neurología previa
        '861402' => ['890264', '890364'], // Fisiatria → requiere consulta fisiatría previa
    ];

    /**
     * @param string $cupId
     * @param int $espacios
     * @param string|null $afterDate Fecha y hora mínima para buscar slots (Y-m-d H:i). Si se proporciona, solo retorna slots posteriores.
     * @return AvailableSlotDTO[]
     */
    public function execute(array $procedures, int $espacios, int $patientAge, bool $isContrasted, bool $isSedated = false, string $patientId = '', ?string $afterDate = null): array
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

        // Filtrar doctores que no atienden pacientes de cierta edad usando array estático
        $doctoresFiltrados = $this->filterDoctorsByAge($doctores, $patientAge);

        // Caso especial: CUPS que requieren médico de cita previa
        // Solo buscar agendas del médico que atendió la última consulta del paciente
        if (isset(self::CUPS_REQUIRES_PREVIOUS_DOCTOR[$cupId]) && !empty($patientId)) {
            $requiredPreviousCups = self::CUPS_REQUIRES_PREVIOUS_DOCTOR[$cupId];
            $lastDoctorDocument = $this->appointmentRepository->findLastDoctorForPatientByCups(
                $patientId,
                $requiredPreviousCups
            );

            if ($lastDoctorDocument) {
                Log::info('Filtering doctors for CUPS requiring previous consultation', [
                    'cup_id' => $cupId,
                    'patient_id' => $patientId,
                    'last_doctor' => $lastDoctorDocument,
                    'required_previous_cups' => $requiredPreviousCups
                ]);

                // Filtrar para dejar solo el médico de la última consulta
                $doctoresFiltrados = array_filter($doctoresFiltrados, function ($doctor) use ($lastDoctorDocument) {
                    return $doctor['doctor_document'] === $lastDoctorDocument;
                });

                if (empty($doctoresFiltrados)) {
                    Log::warning('Previous consultation doctor not available for this procedure', [
                        'patient_id' => $patientId,
                        'last_doctor' => $lastDoctorDocument,
                        'cup_id' => $cupId
                    ]);
                    return [];
                }
            } else {
                Log::warning('No previous consultation found for patient - cannot schedule procedure', [
                    'patient_id' => $patientId,
                    'cup_id' => $cupId,
                    'required_previous_cups' => $requiredPreviousCups
                ]);
                return [];
            }
        }

        $doctorDocuments = array_column($doctoresFiltrados, 'doctor_document');
        $doctorMap = [];
        foreach ($doctoresFiltrados as $doctor) {
            $doctorMap[$doctor['doctor_document']] = $doctor['doctor_full_name'] ?? $doctor['doctor_document'];
        }

        // Verificar si el paciente es de SAN02 para aplicar validación de riesgo compartido
        $isSan02Patient = false;
        if (!empty($patientId)) {
            $patient = $this->patientRepository->findById($patientId);
            $isSan02Patient = $patient && ($patient['entity_code'] ?? '') === 'SAN02';
        }

        // Unir todos los días laborales futuros de todos los doctores

        $daysAllDoctors = $this->scheduleRepository->findFutureWorkingDaysByDoctors($doctorDocuments);

        // Si es sedación, filtrar para solo mostrar días de agendas de sedación
        if ($isSedated) {
            // Primero, obtener los IDs de las agendas de sedación para cada doctor
            $sedationAgendaIds = [];
            foreach ($doctorDocuments as $doctorDocument) {
                $sedationSchedule = $this->scheduleRepository->findScheduleByDoctorAndType($doctorDocument, 'sedacion');
                if ($sedationSchedule) {
                    $sedationAgendaIds[] = $sedationSchedule['id'];
                    Log::info('Found sedation agenda for doctor', [
                        'doctor_document' => $doctorDocument,
                        'sedation_agenda_id' => $sedationSchedule['id'],
                        'sedation_agenda_name' => $sedationSchedule['name']
                    ]);
                }
            }

            if (empty($sedationAgendaIds)) {
                Log::warning('No sedation agendas found for any doctor', [
                    'doctor_documents' => $doctorDocuments
                ]);
                return [];
            }

            // Filtrar daysAllDoctors para solo incluir días con agenda_id de sedación
            $daysAllDoctors = array_filter($daysAllDoctors, function ($day) use ($sedationAgendaIds) {
                return in_array($day['agenda_id'], $sedationAgendaIds);
            });
            $daysAllDoctors = array_values($daysAllDoctors); // Reindexar array

            Log::info('Filtered days for sedation agendas only', [
                'sedation_agenda_ids' => $sedationAgendaIds,
                'filtered_days_count' => count($daysAllDoctors),
                'filtered_days' => $daysAllDoctors
            ]);

            if (empty($daysAllDoctors)) {
                Log::warning('No working days configured for sedation agendas', [
                    'sedation_agenda_ids' => $sedationAgendaIds
                ]);
                return [];
            }
        }

        if ($isContrasted) {
            Log::info('Aplicando restricciones para examen contrastado', [
                'restricciones' => 'No sábados, límite 17:00'
            ]);
        }

        if ($isSedated) {
            Log::info('Aplicando restricciones para procedimiento con sedación', [
                'restricciones' => 'Solo agendas con "sedación" en el nombre'
            ]);
        }

        $slots = [];
        $scheduleCache = [];
        $scheduleConfigCache = [];
        $sharedRiskLimitCache = []; // Cache para límites de riesgo compartido por mes

        // Parsear afterDate para paginación
        $afterDateTime = null;
        $afterDateOnly = null;
        if ($afterDate) {
            $afterDateTime = \DateTime::createFromFormat('Y-m-d H:i', $afterDate);
            $afterDateOnly = $afterDateTime ? $afterDateTime->format('Y-m-d') : null;
        }

        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ($daysAllDoctors as $day) {
            if (count($slots) >= 5) break;

            $doctorDocument = $day['doctor_document'];
            $doctorFullName = $doctorMap[$doctorDocument] ?? $doctorDocument;

            $weekday = (int)date('w', strtotime($day['date']));

            // Si hay afterDate, saltar días anteriores a esa fecha
            if ($afterDateOnly && $day['date'] < $afterDateOnly) {
                continue;
            }

            // Si es examen contrastado y es sábado, saltar este día
            if ($isContrasted && $weekday === 6) {
                continue;
            }

            // Validación de modelo de riesgo compartido para pacientes SAN02
            if ($isSan02Patient) {
                $slotMonth = substr($day['date'], 0, 7); // Formato Y-m
                $cacheKey = $cupId . '_' . $slotMonth;

                if (!isset($sharedRiskLimitCache[$cacheKey])) {
                    $sharedRiskLimitCache[$cacheKey] = $this->cupsGroupFilterService->isCupAtLimitForDate(
                        $cupId,
                        $day['date'],
                        'datosipsndx'
                    );
                }

                if ($sharedRiskLimitCache[$cacheKey]) {
                    // Este mes ya alcanzó el límite para este grupo de CUPS, saltar
                    continue;
                }
            }

            // Determinar el tipo de agenda a buscar
            $scheduleType = $cupInfo['type'];
            if ($isSedated) {
                $scheduleType = 'sedacion'; // Forzar búsqueda de agendas de sedación
            }

            // Variable para el ID de agenda efectivo (para el DTO)
            $effectiveAgendaId = $day['agenda_id'];

            if ($day['agenda_id'] == 0) {
                // Si es sedación, buscar agenda de sedación del doctor
                if ($isSedated) {
                    $cacheKey = 'doctor_' . $doctorDocument . '_sedacion';
                    if (isset($scheduleCache[$cacheKey])) {
                        $schedule = $scheduleCache[$cacheKey];
                    } else {
                        $schedule = $this->scheduleRepository->findScheduleByDoctorAndType($doctorDocument, 'sedacion');
                        $scheduleCache[$cacheKey] = $schedule;

                        Log::info('Schedule lookup for sedation (agenda_id=0)', [
                            'doctor_document' => $doctorDocument,
                            'schedule_found' => $schedule ? true : false,
                            'schedule_name' => $schedule['name'] ?? 'N/A',
                            'schedule_id' => $schedule['id'] ?? 'N/A'
                        ]);
                    }
                    if (!$schedule) continue;
                    // Usar el ID de la agenda de sedación encontrada
                    $effectiveAgendaId = $schedule['id'];
                } else {
                    $schedule = ['id' => 0];
                }
            } else {
                $cacheKey = $day['agenda_id'] . '_' . ($scheduleType ?? 'default');
                if (isset($scheduleCache[$cacheKey])) {
                    $schedule = $scheduleCache[$cacheKey];
                } else {
                    $schedule = $this->scheduleRepository->findByScheduleId($day['agenda_id'], $scheduleType);
                    $scheduleCache[$cacheKey] = $schedule;

                    Log::info('Schedule lookup', [
                        'agenda_id' => $day['agenda_id'],
                        'scheduleType' => $scheduleType,
                        'isSedated' => $isSedated,
                        'schedule_found' => $schedule ? true : false,
                        'schedule_name' => $schedule['name'] ?? 'N/A'
                    ]);
                }
                if (!$schedule) continue;
            }
            // Cache de configuración de agenda
            if (isset($scheduleConfigCache[$schedule['id']])) {
                $scheduleConfig = $scheduleConfigCache[$schedule['id']];
            } else {
                $scheduleConfig = $this->scheduleConfigRepository->findByScheduleId($schedule['id'], $doctorDocument);
                $scheduleConfigCache[$schedule['id']] = $scheduleConfig;
            }
            if (!$scheduleConfig) continue;

            $assignedAppointments = $this->appointmentRepository->findByAgendaAndDate($effectiveAgendaId, $day['date']);

            Log::info('Generating slots', [
                'date' => $day['date'],
                'original_agenda_id' => $day['agenda_id'],
                'effective_agenda_id' => $effectiveAgendaId,
                'isSedated' => $isSedated,
                'schedule_id' => $schedule['id'] ?? 'N/A'
            ]);
            $morningStartKey = 'morning_start_' . $days[$weekday];
            $morningEndKey = 'morning_end_' . $days[$weekday];
            $afternoonStartKey = 'afternoon_start_' . $days[$weekday];
            $afternoonEndKey = 'afternoon_end_' . $days[$weekday];

            $workHours = [];
            if ($day['morning_enabled'] == -1 && !empty($scheduleConfig[$morningStartKey]) && !empty($scheduleConfig[$morningEndKey])) {
                $morningEnd = $this->formatHour($scheduleConfig[$morningEndKey]);

                // Si es contrastado, limitar la hora de fin a 17:00
                if ($isContrasted && $morningEnd > '17:00') {
                    $morningEnd = '17:00';
                }

                $workHours[] = [
                    'start' => $this->formatHour($scheduleConfig[$morningStartKey]),
                    'end' => $morningEnd,
                ];
            }
            if ($day['afternoon_enabled'] == -1 && !empty($scheduleConfig[$afternoonStartKey]) && !empty($scheduleConfig[$afternoonEndKey])) {
                $afternoonStart = $this->formatHour($scheduleConfig[$afternoonStartKey]);
                $afternoonEnd = $this->formatHour($scheduleConfig[$afternoonEndKey]);

                // Si es contrastado, limitar la hora de fin a 17:00
                if ($isContrasted && $afternoonEnd > '17:00') {
                    $afternoonEnd = '17:00';
                }

                // Si el inicio es después de las 17:00 y es contrastado, saltar este periodo
                if ($isContrasted && $afternoonStart >= '17:00') {
                    continue;
                }

                $workHours[] = [
                    'start' => $afternoonStart,
                    'end' => $afternoonEnd,
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

                    // Si es contrastado, verificar que el slot termine antes de las 17:00
                    if ($isContrasted) {
                        $slotEndStr = $slotEnd->format('H:i');
                        if ($slotEndStr > '17:00') {
                            break; // No generar más slots este periodo
                        }
                    }

                    if (!in_array($slotStartStr, $assignedTimes)) {
                        // Si hay afterDate y es el mismo día, saltar slots con hora <= afterDateTime
                        if ($afterDateTime && $day['date'] === $afterDateOnly) {
                            if ($slotStart <= $afterDateTime) {
                                $start->modify("+{$duration} minutes");
                                continue;
                            }
                        }

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
                                $effectiveAgendaId,
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
                            $effectiveAgendaId,
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

    /**
     * Filtra doctores que no atienden pacientes de cierta edad usando array estático
     * Array estático: documento_doctor => edad_mínima_que_atiene
     */
    private function filterDoctorsByAge(array $doctores, int $patientAge): array
    {
        $doctorAgeRestrictions = [
            '74372158' => 5,
            '7178922' => 18
        ];

        $filteredDoctors = [];
        foreach ($doctores as $doctor) {
            $doctorDocument = $doctor['doctor_document'];

            if (isset($doctorAgeRestrictions[$doctorDocument])) {
                $minAge = $doctorAgeRestrictions[$doctorDocument];

                if ($patientAge < $minAge) {
                    continue;
                }
            }

            $filteredDoctors[] = $doctor;
        }

        return $filteredDoctors;
    }
}
