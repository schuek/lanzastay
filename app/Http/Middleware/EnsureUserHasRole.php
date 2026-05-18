<?php

namespace App\Http\Middleware;

use App\Support\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if (UserRole::isAdmin($user->role)) {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            return $this->deny($request);
        }

        return $next($request);
    }

    private function deny(Request $request): Response
    {
        $message = 'No tienes permiso para acceder a esta sección.';

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            abort(403, $message);
        }

        return redirect()
            ->route('dashboard')
            ->with('error', $message);
    }
}
