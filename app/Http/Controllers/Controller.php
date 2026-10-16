<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

abstract class Controller
{
    public ?User $user;

    public ?TahunAjaran $tahunAjaran;

    public function __construct()
    {
        Inertia::share([
            'tanggal_sekarang' => Carbon::now()->translatedFormat('D, d-M-Y'),
        ]);
        $this->user = Auth::guard('web')->user();

        // // Cache hanya ID (scalar) karena config serializable_classes=false melarang
        // // unserialize objek model dari cache.
        // $tahunAjaranId = Cache::rememberForever(
        //     'tahun-ajaran-aktif',
        //     fn () => TahunAjaran::whereActive(true)->value('id'),
        // );

        $this->tahunAjaran = TahunAjaran::whereActive(true)->first();
    }
}
