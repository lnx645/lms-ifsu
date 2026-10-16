<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\BaseAppController;
use App\Models\Notifikasi;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class NotifikasiController extends BaseAppController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, $next) {
                if ($request->user()?->role !== 'siswa') {
                    abort(403);
                }

                return $next($request);
            }),
        ];
    }

    public function index(Request $request): Response
    {
        $notifikasis = Notifikasi::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (Notifikasi $n): array => [
                'id' => $n->id,
                'tipe' => $n->tipe,
                'judul' => $n->judul,
                'pesan' => $n->pesan,
                'tautan' => $n->tautan,
                'dibaca' => $n->dibaca_at !== null,
                'waktu' => $n->created_at->diffForHumans(),
            ]);

        return Inertia::render('siswa/Notifikasi/Index', [
            'notifikasis' => $notifikasis,
        ]);
    }

    public function baca(Request $request, Notifikasi $notifikasi): RedirectResponse
    {
        abort_unless($notifikasi->user_id === $request->user()->id, 404);

        $notifikasi->tandaiDibaca();

        if ($notifikasi->tautan) {
            return Redirect::to($notifikasi->tautan);
        }

        return Redirect::back();
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        Notifikasi::where('user_id', $request->user()->id)
            ->whereNull('dibaca_at')
            ->update(['dibaca_at' => now()]);

        Toast::success('Semua notifikasi ditandai dibaca.');

        return Redirect::back();
    }
}
