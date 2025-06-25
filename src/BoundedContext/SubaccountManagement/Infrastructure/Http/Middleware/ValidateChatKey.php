<?php

namespace Core\BoundedContext\SubaccountManagement\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateChatKey
{
    public function handle(Request $request, Closure $next)
    {
        $expectedKey = config('services.messagebird.chat_key');
        $actualKey = $request->header('chat-key');

        if (!$expectedKey || $actualKey !== $expectedKey) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
