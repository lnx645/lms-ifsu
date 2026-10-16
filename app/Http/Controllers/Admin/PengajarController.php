<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class PengajarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $jenisKelamin = $request->query('jenis_kelamin');
        $isAktif = $request->query('is_aktif');
        $pendidikan = $request->query('pendidikan_terakhir');
        $walikelas = $request->query('walikelas');

        $query = Guru::query()->with('walikelas');
        if ($search) {
            $query->where('nama_lengkap', 'like', "%{$search}%");
        }
        if (in_array($jenisKelamin, ['L', 'P'], true)) {
            $query->where('jenis_kelamin', $jenisKelamin);
        }
        if ($isAktif === '1') {
            $query->where('is_aktif', true);
        } elseif ($isAktif === '0') {
            $query->where('is_aktif', false);
        }
        if ($pendidikan) {
            $query->where('pendidikan_terakhir', $pendidikan);
        }
        if ($walikelas === '1') {
            $query->has('walikelas');
        } elseif ($walikelas === '0') {
            $query->doesntHave('walikelas');
        }

        $pengajar = $query->orderBy('nama_lengkap')
            ->paginate(10)
            ->through(fn (Guru $guru) => [
                'id' => $guru->id,
                'nama_lengkap' => $guru->nama_lengkap,
                'jenis_kelamin' => $guru->jenis_kelamin,
                'pendidikan_terakhir' => $guru->pendidikan_terakhir,
                'alamat' => $guru->alamat,
                'foto_profil' => $guru->foto_profil,
                'is_aktif' => $guru->is_aktif,
                'walikelas' => $guru->walikelas->map(function ($k) {
                    return ['id' => $k->id, 'nama' => $k->nama];
                })->all(),
            ]);

        $pendidikanOptions = Guru::query()
            ->whereNotNull('pendidikan_terakhir')
            ->distinct()
            ->pluck('pendidikan_terakhir')
            ->filter()
            ->values()
            ->all();

        return Inertia::render('admin/Pengajar/Index', [
            'pengajar' => Inertia::merge($pengajar),
            'filters' => [
                'search' => $search ?? '',
                'jenis_kelamin' => $jenisKelamin ?? '',
                'is_aktif' => $isAktif ?? '',
                'pendidikan_terakhir' => $pendidikan ?? '',
                'walikelas' => $walikelas ?? '',
            ],
            'pendidikanOptions' => $pendidikanOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge($this->normalizeJenisKelamin($request));

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:50'],
            'pendidikan_terakhir' => ['nullable', 'string', 'max:50'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'alamat' => ['nullable', 'string'],
            'is_aktif' => ['boolean'],
        ], [], [
            'nama_lengkap' => 'Nama Lengkap',
            'pendidikan_terakhir' => 'Pendidikan Terakhir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'alamat' => 'Alamat',
            'is_aktif' => 'Aktif',
        ]);

        if ($request->hasFile('foto_profil')) {
            $data['foto_profil'] = $request->file('foto_profil')->store('guru', 'public');
        }

        Guru::create($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Pengajar berhasil ditambahkan.',
        ]);

        return Redirect::route('admin.pengajar.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Guru $pengajar): RedirectResponse
    {
        $request->merge($this->normalizeJenisKelamin($request));

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:50'],
            'pendidikan_terakhir' => ['nullable', 'string', 'max:50'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'alamat' => ['nullable', 'string'],
            'is_aktif' => ['boolean'],
        ], [], [
            'nama_lengkap' => 'Nama Lengkap',
            'pendidikan_terakhir' => 'Pendidikan Terakhir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'alamat' => 'Alamat',
            'is_aktif' => 'Aktif',
        ]);

        if ($request->hasFile('foto_profil')) {
            $data['foto_profil'] = $request->file('foto_profil')->store('guru', 'public');
        }

        $pengajar->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Pengajar berhasil diperbarui.',
        ]);

        return Redirect::route('admin.pengajar.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Guru $pengajar): RedirectResponse
    {
        if ($pengajar->user || $pengajar->kelas()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Pengajar tidak dapat dihapus karena masih memiliki user atau kelas terkait.',
            ]);

            return Redirect::route('admin.pengajar.index');
        }

        $pengajar->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Pengajar berhasil dihapus.',
        ]);

        return Redirect::route('admin.pengajar.index');
    }

    /**
     * Extract the scalar value from the select component payload
     * (svelte-select returns { value, label } instead of the raw value).
     */
    private function normalizeJenisKelamin(Request $request): array
    {
        $value = $request->input('jenis_kelamin');

        if (is_array($value)) {
            $value = $value['value'] ?? reset($value);
        } elseif (is_object($value)) {
            $value = $value->value ?? null;
        }

        return ['jenis_kelamin' => $value];
    }
}
