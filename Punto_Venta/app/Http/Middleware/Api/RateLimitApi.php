<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitApi
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $client = $request->api_client;
        
        if (!$client) {
            return $next($request);
        }

        $key = 'api_rate_limit:' . $client->id;
        $maxAttempts = $client->rate_limit_per_minute ?? config('api.rate_limit.max_attempts');
        $decayMinutes = config('api.rate_limit.decay_minutes');

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Demasiadas peticiones. Intente nuevamente en ' . $seconds . ' segundos',
                    'retry_after' => $seconds
                ]
            ], 429)->header('Retry-After', $seconds);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Agregar headers de rate limit
        $remaining = $maxAttempts - RateLimiter::attempts($key);
        
        return $response
            ->header('X-RateLimit-Limit', $maxAttempts)
            ->header('X-RateLimit-Remaining', $remaining)
            ->header('X-RateLimit-Reset', now()->addMinutes($decayMinutes)->timestamp);
    }
}
