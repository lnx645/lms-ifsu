<?php

namespace App\Http\Middleware;

use App\Models\Siswa;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UjianAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $nisn = $request->session()->get('ujian_nisn');
        if (! $nisn) {
            return redirect()->route('ujian.login');
        }

        // JANGAN Auth::login — biarkan session terpisah dari dashboard.
        // Controller membaca NISN dari session via $request->attributes.
        $siswa = Siswa::where('nisn', $nisn)->first();
        if (! $siswa) {
            $request->session()->forget(['ujian_nisn', 'mode_ujian']);

            return redirect()->route('ujian.login');
        }
        $request->attributes->set('ujian_nisn', $nisn);
        $request->attributes->set('ujian_siswa', $siswa);

        return $next($request);
    }
}
