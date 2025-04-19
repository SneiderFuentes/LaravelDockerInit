<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\SubaccountManagement\Application\Handlers\GetSubaccountByKeyHandler;
use Core\BoundedContext\SubaccountManagement\Application\Queries\GetSubaccountByKeyQuery;
use Core\BoundedContext\SubaccountManagement\Domain\Exceptions\SubaccountNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class SubaccountController extends Controller
{
    public function __construct(
        private GetSubaccountByKeyHandler $getSubaccountByKeyHandler
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
}
