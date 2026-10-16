<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LinkExternalController extends Controller
{
    public function link(Request $request)
    {
        // Ambil parameter 'l' atau 'link' dari query string
        $url = $request->path();

        if (! $url) {
            return redirect()->back();
        }
        if (! preg_match('~^(?:f|ht)tps?://~i', $url)) {
            $url = 'https://'.$url;
        }

        try {

            $response = Http::timeout(3)->head($url);

            if ($response->failed()) {
                $response = Http::timeout(3)->get($url);
            }
            if ($response->successful() || $response->redirect()) {
                return redirect()->away($url);
            }
        } catch (\Exception $e) {
            // Terjadi timeout, domain mati, atau error koneksi
            // Lanjut ke blok error di bawah
        }

        return redirect()->back()->with('error', 'Tautan eksternal tidak aktif atau tidak dapat diakses.');
    }
}
