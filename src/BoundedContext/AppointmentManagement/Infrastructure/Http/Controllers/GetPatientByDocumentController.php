<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetPatientByDocumentController
{
    public function __construct(private PatientRepositoryInterface $repository) {}

    public function __invoke(string $centerKey, string $document): JsonResponse
    {
        if (!$document) {
            return response()->json(['error' => 'El parámetro document es requerido'], 400);
        }
        $patient = $this->repository->findByDocument($document);
        if (!$patient) {
            return response()->json(['error' => 'Paciente no encontrado'], 404);
        }
        return response()->json($patient);
    }
}
