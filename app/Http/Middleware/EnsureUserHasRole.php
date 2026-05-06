<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if(
            $user === null || ! collect($roles)->contains(fn (string $role): bool => $user->ownsRole($role)),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
