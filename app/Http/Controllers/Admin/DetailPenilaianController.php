<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailPenilaian;
use App\Models\GuruKelas;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class DetailPenilaianController extends Controller
{
    /**
     * Pilih penugasan (guru-kelas-matpel) untuk input nilai sebuah penilaian.
     */
    public function filterSiswa(Request $request, Penilaian $penilaian): Response
    {
        $penugasan = GuruKelas::query()
            ->with(['kelas:id,nama', 'matpel:id,name', 'guru:id,nama_lengkap'])
            ->orderBy('kelas_id')
            ->get()
            ->map(fn (GuruKelas $gk): array => [
                'value' => $gk->id,
                'label' => ($gk->kelas?->nama ?? 'Kelas')
                    .' — '.($gk->matpel?->name ?? 'Matpel')
                    .' — '.($gk->guru?->nama_lengkap ?? 'Guru'),
            ]);

        $guruKelasId = $request->query('guru_kelas_id');
        $guruKelasInfo = null;
        $siswas = null;

        if ($guruKelasId !== null && $guruKelasId !== '') {
            $guruKelas = GuruKelas::with(['kelas:id,nama', 'matpel:id,name'])->findOrFail($guruKelasId);

            $guruKelasInfo = [
                'id' => $guruKelas->id,
                'kelas' => $guruKelas->kelas?->nama,
                'matpel' => $guruKelas->matpel?->name,
            ];

            $detail = DetailPenilaian::with('guru:id,nama_lengkap')
                ->where('penilaian_id', $penilaian->id)
                ->where('guru_kelas_id', $guruKelas->id)
                ->get()
                ->keyBy('siswa_nisn');

            $siswas = SiswaKelas::query()
                ->with('siswa')
                ->where('kelas_id', $guruKelas->kelas_id)
                ->where('tahun_ajaran_id', $guruKelas->tahun_ajaran_id)
                ->where('active', true)
                ->orderBy('siswa_nisn')
                ->get()
                ->map(fn (SiswaKelas $sk): array => [
                    'nisn' => $sk->siswa_nisn,
                    'nama' => $sk->siswa?->nama_lengkap ?? 'Siswa',
                    'nilai' => $detail->get($sk->siswa_nisn)?->nilai,
                    'sumber' => $detail->get($sk->siswa_nisn)?->sumber,
                    'keterangan' => $detail->get($sk->siswa_nisn)?->keterangan,
                    'guru' => $detail->get($sk->siswa_nisn)?->guru?->nama_lengkap,
                ]);
        }

        return Inertia::render('admin/DetailPenilaian/Filter', [
            'penilaian' => $penilaian,
            'penugasan' => $penugasan,
            'guruKelasInfo' => $guruKelasInfo,
            'selectedGuruKelasId' => $guruKelasId,
            'siswas' => $siswas,
        ]);
    }

    /**
     * Form nilai untuk satu siswa dalam satu penugasan.
     */
    public function detail(Request $request, Penilaian $penilaian, GuruKelas $guruKelas, Siswa $siswa): Response
    {
        $detail = DetailPenilaian::where('penilaian_id', $penilaian->id)
            ->where('guru_kelas_id', $guruKelas->id)
            ->where('siswa_nisn', $siswa->nisn)
            ->first();

        return Inertia::render('admin/DetailPenilaian/Show', [
            'penilaian' => $penilaian,
            'guruKelas' => [
                'id' => $guruKelas->id,
                'kelas' => $guruKelas->kelas?->nama,
                'matpel' => $guruKelas->matpel?->name,
            ],
            'siswa' => $siswa,
            'detail' => $detail,
        ]);
    }

    /**
     * Simpan (atau perbarui) nilai seorang siswa pada penilaian + penugasan.
     */
    public function storeNilai(Request $request, Penilaian $penilaian, GuruKelas $guruKelas, Siswa $siswa): RedirectResponse
    {
        abort_unless(
            SiswaKelas::where('siswa_nisn', $siswa->nisn)
                ->where('kelas_id', $guruKelas->kelas_id)
                ->where('tahun_ajaran_id', $guruKelas->tahun_ajaran_id)
                ->where('active', true)
                ->exists(),
            422
        );

        $data = $request->validate([
            'nilai' => ['required', 'numeric', 'min:0', 'max:'.$penilaian->nilai_maks],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [], [
            'nilai' => 'Nilai',
            'keterangan' => 'Keterangan',
        ]);

        DetailPenilaian::updateOrCreate(
            [
                'penilaian_id' => $penilaian->id,
                'guru_kelas_id' => $guruKelas->id,
                'siswa_nisn' => $siswa->nisn,
            ],
            [
                'tahun_ajaran_id' => $guruKelas->tahun_ajaran_id,
                'nilai' => $data['nilai'],
                'sumber' => 'manual',
                'keterangan' => $data['keterangan'] ?? null,
            ]
        );

        Toast::success('Nilai berhasil disimpan.');

        return Redirect::back();
    }
}
