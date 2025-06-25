<?php

use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\AppointmentController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\AsyncAppointmentController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\AsyncGetAvailableSlotsByCupController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\GetActiveEntitiesController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\GetAvailableSlotsByCupController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\GetPatientByDocumentController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\GetUpcomingAppointmentsByPatientController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\CreatePatientController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\AsyncCreatePatientController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\AsyncGetActiveEntitiesController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\AsyncGetPatientByDocumentController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\AsyncGetUpcomingAppointmentsByPatientController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\AsyncUploadMedicalOrderController;
use Illuminate\Support\Facades\Route;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\WebhookController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\AsyncPlanAppointmentsController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\GlomerularFiltrationController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\ConfirmAppointmentController;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\CheckExistingAppointmentController;


Route::prefix('api/centers/{centerKey}')->group(function () {
    // Agrupar rutas async bajo el middleware
    Route::middleware('validate.async.key')->group(function () {
        Route::post('async-patient/create', AsyncCreatePatientController::class);
        Route::post('async-medical-orders/upload', AsyncUploadMedicalOrderController::class);
        Route::post('async-appointments/plan', AsyncPlanAppointmentsController::class);
        Route::get('async-appointments/pending', [AsyncAppointmentController::class, 'index']);
        Route::post('async-appointments/create', [AsyncAppointmentController::class, 'create']);
        Route::post('async-appointments/available-slots', AsyncGetAvailableSlotsByCupController::class);
        Route::post('calculate-glomerular-filtration', GlomerularFiltrationController::class);
        Route::post('appointments/check-existing', CheckExistingAppointmentController::class);

        Route::get('async-entities', AsyncGetActiveEntitiesController::class);

        Route::post('get-id-from-selection', function (Request $request): JsonResponse {
            $ids = $request->input('ids', []);
            $answer = $request->input('answer');
            $detailsMap = $request->input('details_map', []);

            // Validación básica
            if (empty($ids) || is_null($answer) || empty($detailsMap)) {
                return response()->json(['error' => 'Missing ids, answer, or details_map'], 400);
            }

            $index = (int)$answer;
            $appointmentId = null;
            $appointmentDetailsText = 'No hemos podido encontrar los detalles de la cita seleccionada. Por favor, intente de nuevo.';

            // Lógica de selección segura para obtener el ID
            if (isset($ids[$index])) {
                $appointmentId = $ids[$index];
            } else {
                // Fallback seguro si el índice no es válido
                $appointmentId = $ids[0] ?? 'NA';
            }

            // Usar el ID obtenido para encontrar el texto de los detalles
            if ($appointmentId !== 'NA' && isset($detailsMap[$appointmentId])) {
                $appointmentDetailsText = $detailsMap[$appointmentId];
            }

            return response()->json([
                'appointment_id' => $appointmentId,
                'appointment_details_text' => $appointmentDetailsText
            ]);
        });

        Route::post('select-appointment', function (Request $request): JsonResponse {
            $validated = $request->validate([
                'appointments' => 'required|array',
                'selection' => 'required|integer|min:1',
            ]);

            $appointments = $validated['appointments'];
            $selectionIndex = (int)$validated['selection'] - 1;

            if (!isset($appointments[$selectionIndex])) {
                return response()->json(['error' => 'La selección no es válida.'], 400);
            }

            $selectedAppointment = $appointments[$selectionIndex];

            return response()->json($selectedAppointment);
        });

        Route::get('async-appointments/upcoming/{patientId}', AsyncGetUpcomingAppointmentsByPatientController::class);
        Route::get('async-patient/{document}', AsyncGetPatientByDocumentController::class);

        Route::post('async-appointments/{id}/confirm', [AsyncAppointmentController::class, 'confirm']);
        Route::post('async-appointments/{id}/cancel', [AsyncAppointmentController::class, 'cancel']);
    });


    Route::post('patient/create', CreatePatientController::class);
    Route::get('entities', GetActiveEntitiesController::class);

    Route::get('appointments/pending', [AppointmentController::class, 'index']);
    Route::post('appointments/create', [AppointmentController::class, 'create']);
    Route::post('appointments/available-slots', GetAvailableSlotsByCupController::class);

    Route::post('appointments/{id}/confirm', [AppointmentController::class, 'confirm']);
    Route::post('appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
    Route::get('appointments/upcoming/{patientId}', GetUpcomingAppointmentsByPatientController::class);
    Route::get('patient/{document}', GetPatientByDocumentController::class);
});

Route::prefix('api/webhooks')->group(function () {
    Route::post('/flow', [WebhookController::class, 'handle']);
});
