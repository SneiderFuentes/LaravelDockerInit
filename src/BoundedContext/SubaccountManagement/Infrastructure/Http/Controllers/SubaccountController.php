<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\SubaccountManagement\Application\Handlers\GetSubaccountByKeyHandler;
use Core\BoundedContext\SubaccountManagement\Application\Queries\GetSubaccountByKeyQuery;
use Core\BoundedContext\SubaccountManagement\Domain\Exceptions\SubaccountNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Core\BoundedContext\SubaccountManagement\Domain\Repositories\SubaccountRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\GenericDbAppointmentRepository;

final class SubaccountController extends Controller
{
    public function __construct(
        private GetSubaccountByKeyHandler $getSubaccountByKeyHandler,
        private SubaccountRepositoryInterface $subaccountRepository,
        private GenericDbAppointmentRepository $baseRepository
    ) {}

    public function show(string $key): JsonResponse
    {
        try {
            $query = new GetSubaccountByKeyQuery($key);
            $dto = $this->getSubaccountByKeyHandler->handle($query);

            return new JsonResponse($dto->toArray());
        } catch (SubaccountNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'An unexpected error occurred'], 500);
        }
    }

    public function index(): JsonResponse
    {
        // Implementar con otra query/handler GetAllSubaccountsQuery
        return new JsonResponse(['message' => 'Not implemented yet'], 501);
    }

    public function updateApiCredentials(Request $request, string $key): JsonResponse
    {
        $apiHeader = $request->input('api_header');
        $apiKey = $request->input('api_key');
        if (!$apiHeader || !$apiKey) {
            return new JsonResponse(['error' => 'api_header and api_key are required'], 422);
        }
        $subaccount = $this->subaccountRepository->findByKey($key);
        if (!$subaccount) {
            return new JsonResponse(['error' => 'Subaccount not found'], 404);
        }
        // Actualizar config
        $configArr = [
            'key' => $subaccount->key(),
            'name' => $subaccount->name(),
            'connection' => $subaccount->config()->connection(),
            'tables' => $subaccount->config()->tables(),
            'connections' => $subaccount->config()->connections(),
            'api_header' => $apiHeader,
            'api_key' => $apiKey,
        ];
        $newConfig = \Core\BoundedContext\SubaccountManagement\Domain\ValueObjects\SubaccountConfig::fromArray($configArr);
        $updated = $subaccount->updateConfig($newConfig);
        $this->subaccountRepository->save($updated);
        // Limpiar cache de config
        $this->baseRepository->clearConfigCache($key);
        return new JsonResponse(['message' => 'API credentials updated']);
    }
}
