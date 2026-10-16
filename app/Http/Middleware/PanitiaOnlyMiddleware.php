<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CBT v2: hanya panitia ujian (atau admin) yang boleh lewat.
 */
class PanitiaOnlyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if (! $user) {
            return redirect()->to('app.login');
        }

        if (! in_array($user->role, ['panitia', 'admin'], true)) {
            abort(403);
        }

        return $next($request);
    }
}
