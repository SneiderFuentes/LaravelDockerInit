<?php

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;

class ValidateAsyncApiKey
{
    protected $configService;

    public function __construct(GetSubaccountConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function handle(Request $request, Closure $next)
    {
        $centerKey = $request->route('centerKey');
        $config = $this->configService->execute($centerKey);
        $headerName = $config->apiHeader() ?? 'X-API-KEY';
        $expectedValue = $config->apiKey();
        $actualValue = $request->header($headerName);

        if ($headerName && $expectedValue && $actualValue === $expectedValue) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
