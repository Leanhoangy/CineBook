<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware phân quyền theo vai trò.
 * Cách dùng trong route: ->middleware('role:admin') hoặc ->middleware('role:admin,user')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->hasRole(...$roles)) {
            abort(403, 'Bạn không có quyền truy cập khu vực này.');
        }

        return $next($request);
    }
}
