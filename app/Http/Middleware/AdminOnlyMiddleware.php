<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnlyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');
        if ($user) {
            if ($user->role != 'admin') {
                abort(403);

                return $next($request);
            }

            return $next($request);
        }

        return redirect()->to('app.login');
    }
}
