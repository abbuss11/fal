<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability = '*'): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return response()->json([
                'message' => 'Token API manquant.',
            ], 401);
        }

        $tokenHash = hash('sha256', $plainToken);

        $apiToken = ApiToken::query()
            ->with('user')
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $apiToken || $apiToken->isExpired() || ! $apiToken->user?->is_active) {
            return response()->json([
                'message' => 'Token API invalide ou expire.',
            ], 401);
        }

        if ($ability !== '*' && ! $apiToken->can($ability)) {
            return response()->json([
                'message' => 'Token API sans permission pour cette operation.',
            ], 403);
        }

        $apiToken->forceFill([
            'last_used_at' => now(),
        ])->saveQuietly();

        Auth::setUser($apiToken->user);
        $request->setUserResolver(static fn () => $apiToken->user);
        $request->attributes->set('apiToken', $apiToken);

        return $next($request);
    }
}

