<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SiswaKelasController extends Controller
{
    /**
     * Display the class assignments for a siswa.
     */
    public function index(Siswa $siswa): Response
    {
        return Inertia::render('admin/AturSiswaKelas/Index', [
            'siswa_nisn' => $siswa->nisn,
            'nama' => $siswa->nama_lengkap,
            'nis' => $siswa->nis,
            'foto_profil' => $siswa->foto_profil,
            'kelas_saya' => SiswaKelas::where('siswa_nisn', $siswa->nisn)
                ->with('kelas', 'tahunAjaran')
                ->orderByDesc('active')
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn (SiswaKelas $e) => [
                    'id' => $e->id,
                    'kelas_id' => $e->kelas_id,
                    'pertama_masuk' => $e->pertama_masuk,
                    'nama_kelas' => $e->kelas?->nama,
                    'tahun_ajaran_id' => $e->tahun_ajaran_id,
                    'tahun_ajaran' => $e->tahunAjaran?->name,
                    'active' => $e->active,
                ]),
            'daftar_kelas' => Kelas::leaf()->orderBy('nama')->get(['id', 'nama']),
            'tahun_ajaran' => TahunAjaran::orderByDesc('active')
                ->orderBy('name')
                ->get(['id', 'name', 'active']),
            'tahun_ajaran_aktif' => $this->tahunAjaran?->id,
        ]);
    }

    /**
     * Store a newly created class assignment for the siswa.
     */
    public function store(Request $request, Siswa $siswa): RedirectResponse
    {
        $data = $this->validateAssignment($request, $siswa);

        SiswaKelas::create($data);

        Toast::success('Kelas berhasil ditambahkan.');

        return Redirect::route('admin.siswa.kelas', $siswa);
    }

    /**
     * Update an existing class assignment for the siswa.
     */
    public function update(Request $request, Siswa $siswa, SiswaKelas $siswaKelas): RedirectResponse
    {
        if ($siswaKelas->siswa_nisn !== $siswa->nisn) {
            Toast::error('Penempatan tidak ditemukan untuk peserta didik ini.');

            return Redirect::route('admin.siswa.kelas', $siswa);
        }

        $siswaKelas->update($this->validateAssignment($request, $siswa, $siswaKelas));

        Toast::success('Kelas berhasil diperbarui.');

        return Redirect::route('admin.siswa.kelas', $siswa);
    }

    /**
     * Remove a class assignment for the siswa.
     */
    public function destroy(Siswa $siswa, SiswaKelas $siswaKelas): RedirectResponse
    {
        if ($siswaKelas->siswa_nisn !== $siswa->nisn) {
            Toast::error('Penempatan tidak ditemukan untuk peserta didik ini.');

            return Redirect::route('admin.siswa.kelas', $siswa);
        }

        $siswaKelas->delete();

        Toast::success('Kelas berhasil dihapus.');

        return Redirect::route('admin.siswa.kelas', $siswa);
    }

    /**
     * Validate the assignment request for store/update.
     */
    private function validateAssignment(Request $request, Siswa $siswa, ?SiswaKelas $current = null): array
    {
        $existsRule = Rule::unique('siswa_kelas')
            ->where('siswa_nisn', $siswa->nisn)
            ->where('kelas_id', $request->input('kelas_id'))
            ->where('tahun_ajaran_id', $request->input('tahun_ajaran_id'));

        if ($current) {
            $existsRule = $existsRule->ignore($current->id);
        }

        $data = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id', $existsRule],
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
            'active' => ['boolean'],
            'pertama_masuk' => ['boolean'],
        ], [], [
            'kelas_id' => 'Kelas',
            'tahun_ajaran_id' => 'Tahun Ajaran',
            'active' => 'Aktif',
            'pertama_masuk' => 'Pertama Masuk',
        ]);

        return [
            'siswa_nisn' => $siswa->nisn,
            'kelas_id' => $data['kelas_id'],
            'tahun_ajaran_id' => $data['tahun_ajaran_id'],
            'active' => (bool) ($data['active'] ?? true),
            'pertama_masuk' => (bool) ($data['pertama_masuk'] ?? false),
        ];
    }
}
