<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use App\Services\NaikKelasService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class NaikKelasController extends Controller
{
    public function __construct(protected NaikKelasService $naikKelas) {}

    /**
     * Form pilih tahun ajaran sumber & target.
     */
    public function index(): Response
    {
        return Inertia::render('admin/NaikKelas/Index', [
            'tahun_ajaran' => TahunAjaran::orderByDesc('active')
                ->orderBy('name')
                ->get(['id', 'name', 'active']),
            'preview' => null,
        ]);
    }

    /**
     * Hitung pemetaan kenaikan kelas tanpa menulis DB.
     */
    public function preview(Request $request): Response
    {
        $data = $this->validatedTahunAjaran($request);

        $sumber = TahunAjaran::findOrFail($data['tahun_ajaran_sumber']);
        $target = TahunAjaran::findOrFail($data['tahun_ajaran_target']);

        if ($sumber->id === $target->id) {
            Toast::error('Tahun ajaran sumber dan target harus berbeda.');

            return Inertia::render('admin/NaikKelas/Index', [
                'tahun_ajaran' => TahunAjaran::orderByDesc('active')
                    ->orderBy('name')
                    ->get(['id', 'name', 'active']),
                'preview' => null,
            ]);
        }

        return Inertia::render('admin/NaikKelas/Index', [
            'tahun_ajaran' => TahunAjaran::orderByDesc('active')
                ->orderBy('name')
                ->get(['id', 'name', 'active']),
            'preview' => $this->naikKelas->preview($sumber, $target),
        ]);
    }

    /**
     * Eksekusi kenaikan kelas dalam satu transaksi.
     */
    public function execute(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tahun_ajaran_sumber' => ['required', 'exists:tahun_ajaran,id'],
            'tahun_ajaran_target' => ['required', 'exists:tahun_ajaran,id'],
            'pilihan' => ['required', 'array'],
            'pilihan.*.nisn' => ['required', 'string'],
            'pilihan.*.status' => ['required', 'in:naik,tinggal,lulus'],
            'pilihan.*.kelas_target' => ['nullable', 'integer', 'exists:kelas,id'],
        ], [], [
            'tahun_ajaran_sumber' => 'Tahun Ajaran Sumber',
            'tahun_ajaran_target' => 'Tahun Ajaran Target',
            'pilihan' => 'Pilihan Siswa',
            'pilihan.*.kelas_target' => 'Kelas Tujuan',
        ]);

        $sumber = TahunAjaran::findOrFail($data['tahun_ajaran_sumber']);
        $target = TahunAjaran::findOrFail($data['tahun_ajaran_target']);

        if ($sumber->id === $target->id) {
            Toast::error('Tahun ajaran sumber dan target harus berbeda.');

            return Redirect::route('admin.naik-kelas.index');
        }

        $hasil = $this->naikKelas->execute($sumber, $target, $data['pilihan']);

        Toast::success(
            "Naik kelas selesai: {$hasil['naik']} naik, {$hasil['tinggal']} tinggal, {$hasil['lulus']} lulus.",
        );

        return Redirect::route('admin.naik-kelas.index');
    }

    /**
     * Validasi umum tahun ajaran sumber & target.
     *
     * @return array{tahun_ajaran_sumber: int, tahun_ajaran_target: int}
     */
    private function validatedTahunAjaran(Request $request): array
    {
        return $request->validate([
            'tahun_ajaran_sumber' => ['required', 'exists:tahun_ajaran,id'],
            'tahun_ajaran_target' => ['required', 'exists:tahun_ajaran,id'],
        ], [], [
            'tahun_ajaran_sumber' => 'Tahun Ajaran Sumber',
            'tahun_ajaran_target' => 'Tahun Ajaran Target',
        ]);
    }
}
