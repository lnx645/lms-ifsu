<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penilaian;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PenilaianController extends Controller
{
    /**
     * Display a listing of penilaian.
     */
    public function index(Request $request): Response
    {
        $penilaian = Penilaian::orderBy('nama')->get(['id', 'nama', 'deskripsi', 'tipe', 'nilai_maks', 'bobot', 'aktif', 'sumber']);

        return Inertia::render('admin/Penilaian/Index', [
            'penilaian' => $penilaian,
        ]);
    }

    /**
     * Store a newly created penilaian.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'tipe' => ['required', 'string', Rule::in(['kognitif', 'sikap', 'tugas', 'cbt'])],
            'nilai_maks' => ['required', 'numeric', 'min:0'],
            'bobot' => ['required', 'numeric', 'min:0'],
            'aktif' => ['boolean'],
            'sumber' => ['prohibited'],
        ]);

        Penilaian::create($data);
        Toast::success('Penilaian berhasil ditambahkan.');

        return Redirect::route('admin.penilaian.index');
    }

    /**
     * Display the specified penilaian.
     */
    public function show(Request $request, Penilaian $penilaian): Response
    {
        // Load related kelas and detail count for display.
        $penilaian->load('kelas', 'detailPenilaian');

        return Inertia::render('admin/Penilaian/Show', [
            'penilaian' => $penilaian,
        ]);
    }

    /**
     * Update the specified penilaian.
     */
    public function update(Request $request, Penilaian $penilaian): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'tipe' => ['required', 'string', Rule::in(['kognitif', 'sikap', 'tugas', 'cbt'])],
            'nilai_maks' => ['required', 'numeric', 'min:0'],
            'bobot' => ['required', 'numeric', 'min:0'],
            'aktif' => ['boolean'],
            'sumber' => ['prohibited'],
        ]);

        $penilaian->update($data);
        Toast::success('Penilaian berhasil diperbarui.');

        return Redirect::route('admin.penilaian.index');
    }

    /**
     * Remove the specified penilaian.
     */
    public function destroy(Request $request, Penilaian $penilaian): RedirectResponse
    {
        // Prevent deletion if there are detail scores attached.
        if ($penilaian->detailPenilaian()->exists()) {
            Toast::error('Penilaian tidak dapat dihapus karena sudah memiliki nilai.');

            return Redirect::back();
        }
        $penilaian->delete();
        Toast::success('Penilaian berhasil dihapus.');

        return Redirect::route('admin.penilaian.index');
    }
}
