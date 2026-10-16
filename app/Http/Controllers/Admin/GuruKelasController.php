<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\TahunAjaran;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GuruKelasController extends Controller
{
    /**
     * Display the class assignments (penugasan) for a guru.
     */
    public function index(Guru $guru): Response
    {
        $kelasIds = GuruKelas::where('guru_id', $guru->id)
            ->distinct()
            ->pluck('kelas_id')
            ->all();

        $kelasSaya = Kelas::whereIn('id', $kelasIds)
            ->with([
                'guruKelas' => fn ($q) => $q->where('guru_id', $guru->id),
                'guruKelas.matpel',
                'guruKelas.tahunAjaran',
            ])
            ->orderBy('nama')
            ->get()
            ->map(function (Kelas $kelas): array {
                $matpels = $kelas->guruKelas->map(function (GuruKelas $e): array {
                    return [
                        'id' => $e->id,
                        'nama' => $e->matpel?->name,
                        'matpel_id' => $e->matpel_id,
                        'kelas_id' => $e->kelas_id,
                        'tahun_ajaran_id' => $e->tahun_ajaran_id,
                        'aktif' => $e->aktif,
                    ];
                });

                return [
                    'nama_kelas' => $kelas->nama,
                    'matpels' => $matpels,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('admin/AturGuruKelas/Index', [
            'guru_id' => $guru->id,
            'nama' => $guru->nama_lengkap,
            'nip' => $guru->nip,
            'foto_profil' => $guru->foto_profil,
            'kelas' => $kelasSaya,
            'daftar_kelas' => Kelas::leaf()->orderBy('nama')->get(['id', 'nama']),
            'matpels' => Matpel::orderBy('name')->get(['id', 'name']),
            'tahun_ajaran' => TahunAjaran::orderByDesc('active')
                ->orderBy('name')
                ->get(['id', 'name', 'active']),
            'tahun_ajaran_aktif' => $this->tahunAjaran?->id,
        ]);
    }

    /**
     * Store a newly created class assignment for the guru.
     */
    public function store(Request $request, Guru $guru): RedirectResponse
    {
        $guruKelas = $this->validateAssignment($request, $guru);

        GuruKelas::create($guruKelas);

        Toast::success('Plotting kelas berhasil ditambahkan.');

        return Redirect::route('admin.pengajar.guru-kelas', $guru);
    }

    /**
     * Update an existing class assignment for the guru.
     */
    public function update(Request $request, Guru $guru, GuruKelas $guruKelas): RedirectResponse
    {
        if ($guruKelas->guru_id !== $guru->id) {
            Toast::error('Penugasan tidak ditemukan untuk guru ini.');

            return Redirect::route('admin.pengajar.guru-kelas', $guru);
        }

        $guruKelas->update($this->validateAssignment($request, $guru, $guruKelas));

        Toast::success('Plotting kelas berhasil diperbarui.');

        return Redirect::route('admin.pengajar.guru-kelas', $guru);
    }

    /**
     * Remove a class assignment for the guru.
     */
    public function destroy(Guru $guru, GuruKelas $guruKelas): RedirectResponse
    {
        if ($guruKelas->guru_id !== $guru->id) {
            Toast::error('Penugasan tidak ditemukan untuk guru ini.');

            return Redirect::route('admin.pengajar.guru-kelas', $guru);
        }

        $guruKelas->delete();

        Toast::success('Plotting kelas berhasil dihapus.');

        return Redirect::route('admin.pengajar.guru-kelas', $guru);
    }

    /**
     * Validate the assignment request for store/update.
     */
    private function validateAssignment(Request $request, Guru $guru, ?GuruKelas $current = null): array
    {
        $existsRule = Rule::unique('guru_kelas')
            ->where('guru_id', $guru->id)
            ->where('matpel_id', $request->input('matpel_id'))
            ->where('tahun_ajaran_id', $request->input('tahun_ajaran_id'));

        if ($current) {
            $existsRule = $existsRule->ignore($current->id);
        }

        $data = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id', $existsRule],
            'matpel_id' => ['required', 'exists:matpels,id'],
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
            'aktif' => ['boolean'],
        ], [], [
            'kelas_id' => 'Kelas',
            'matpel_id' => 'Mata Pelajaran',
            'tahun_ajaran_id' => 'Tahun Ajaran',
            'aktif' => 'Aktif',
        ]);

        return [
            'guru_id' => $guru->id,
            'kelas_id' => $data['kelas_id'],
            'matpel_id' => $data['matpel_id'],
            'tahun_ajaran_id' => $data['tahun_ajaran_id'],
            'aktif' => (bool) ($data['aktif'] ?? true),
        ];
    }
}
