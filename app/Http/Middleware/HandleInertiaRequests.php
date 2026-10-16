<?php

namespace App\Http\Middleware;

use App\Models\Notifikasi;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Halaman login ujian tidak butuh data auth
        if ($request->routeIs('ujian.login')) {
            return [
                ...parent::share($request),
                'name' => config('app.name'),
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'tahunAjaranAktif' => TahunAjaran::where('active', true)->first(),
            'auth' => [
                'user' => $request->user()?->load('siswa', 'siswa.kelas.jurusan')->load('guru'),
            ],
            'notifBelumDibaca' => $request->user()
                ? Notifikasi::where('user_id', $request->user()->id)->belumDibaca()->count()
                : 0,
        ];
    }
}
