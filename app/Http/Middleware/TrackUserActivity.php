<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user && $this->shouldUpdatePresence($user)) {
            $user->forceFill([
                'last_seen_at' => now(),
            ])->saveQuietly();
        }

        return $next($request);
    }

    private function shouldUpdatePresence(User $user): bool
    {
        if (! $user->last_seen_at) {
            return true;
        }

        return $user->last_seen_at->lt(now()->subMinutes(3));
    }
}
