<?php

namespace Core\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VerifyBirdSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // $signature = $request->header('X-Bird-Signature');
        // $timestamp = $request->header('X-Bird-Timestamp');
        // $payload = $request->getContent();

        // if (!$this->isValidSignature($signature, $timestamp, $payload)) {
        //     Log::warning('Invalid Bird webhook signature', [
        //         'ip' => $request->ip(),
        //         'signature' => $signature,
        //         'timestamp' => $timestamp
        //     ]);

        //     throw new AccessDeniedHttpException('Invalid signature');
        // }

        return $next($request);
    }

    /**
     * Verify if the signature is valid.
     *
     * @param string|null $signature
     * @param string|null $timestamp
     * @param string $payload
     * @return bool
     */
    private function isValidSignature(?string $signature, ?string $timestamp, string $payload): bool
    {
        if (!$signature || !$timestamp) {
            return false;
        }

        // Check if the timestamp is not too old (15 minutes)
        if (time() - intval($timestamp) > 900) {
            return false;
        }

        $secret = config('services.bird.webhook_secret');

        if (!$secret) {
            Log::error('Bird webhook secret not configured');
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $timestamp . $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
