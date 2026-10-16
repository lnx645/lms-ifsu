<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Services\SiswaImportService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SiswaController extends Controller
{
    /**
     * Display a listing of the peserta didik.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $jenisKelamin = $request->query('jenis_kelamin');
        $isAktif = $request->query('is_aktif');
        $punyaKelas = $request->query('punya_kelas');
        $punyaAkun = $request->query('punya_akun');
        $kelasId = $request->query('kelas');
        $tahunAjaranParam = $request->query('tahun_ajaran');

        $tahunAjaranId = $tahunAjaranParam
            ? (int) $tahunAjaranParam
            : $this->tahunAjaran?->id;

        $kelasFilter = function ($q) use ($tahunAjaranId, $kelasId) {
            $q->where('tahun_ajaran_id', $tahunAjaranId);
            if ($kelasId) {
                $q->where('kelas_id', (int) $kelasId);
            }
        };

        $query = Siswa::query()->withExists('user')->with([
            'siswaKelas' => fn ($q) => $q
                ->with('kelas', 'tahunAjaran')
                ->where('tahun_ajaran_id', $tahunAjaranId),
        ]);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        if (in_array($jenisKelamin, ['L', 'P'], true)) {
            $query->where('jenis_kelamin', $jenisKelamin);
        }
        if ($isAktif === '1') {
            $query->where('is_aktif', true);
        } elseif ($isAktif === '0') {
            $query->where('is_aktif', false);
        }
        if ($punyaKelas === '0') {
            $query->whereDoesntHave('siswaKelas', $kelasFilter);
        } else {
            $query->whereHas('siswaKelas', $kelasFilter);
        }
        if ($punyaAkun === '1') {
            $query->has('user');
        } elseif ($punyaAkun === '0') {
            $query->doesntHave('user');
        }

        $siswa = $query->orderBy('nama_lengkap')
            ->paginate(10)
            ->through(fn (Siswa $item) => [
                'id' => $item->nisn,
                'nisn' => $item->nisn,
                'nis' => $item->nis,
                'nama_lengkap' => $item->nama_lengkap,
                'tempat_lahir' => $item->tempat_lahir,
                'tanggal_lahir' => $item->tanggal_lahir,
                'jenis_kelamin' => $item->jenis_kelamin,
                'alamat' => $item->alamat,
                'foto_profil' => $item->foto_profil,
                'is_aktif' => $item->is_aktif,
                'kelas' => ($kelasSiswa = $item->siswaKelas->first())?->kelas ? [
                    'id' => $kelasSiswa->kelas->id,
                    'nama' => $kelasSiswa->kelas->nama,
                ] : null,
                'tahun_ajaran' => $kelasSiswa?->tahunAjaran?->name,
                'punya_akun' => $item->user_exists,
            ]);

        $jenisKelaminOptions = [
            ['value' => 'L', 'label' => 'Laki-laki'],
            ['value' => 'P', 'label' => 'Perempuan'],
        ];

        $tahunAjaranOptions = TahunAjaran::orderByDesc('active')
            ->orderBy('name')
            ->get(['id', 'name', 'active']);

        $kelasOptions = Kelas::whereIn(
            'id',
            SiswaKelas::where('tahun_ajaran_id', $tahunAjaranId)
                ->distinct()
                ->pluck('kelas_id'),
        )
            ->orderBy('nama')
            ->get(['id', 'nama']);

        return Inertia::render('admin/Siswa/Index', [
            'siswa' => Inertia::merge($siswa),
            'filters' => [
                'search' => $search ?? '',
                'jenis_kelamin' => $jenisKelamin ?? '',
                'is_aktif' => $isAktif ?? '',
                'punya_kelas' => $punyaKelas ?? '',
                'punya_akun' => $punyaAkun ?? '',
                'kelas' => $kelasId ?? '',
                'tahun_ajaran' => $tahunAjaranParam ?? $tahunAjaranId,
            ],
            'jenisKelaminOptions' => $jenisKelaminOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'kelasOptions' => $kelasOptions,
        ]);
    }

    /**
     * Store a newly created peserta didik in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSiswa($request);

        if ($request->hasFile('foto_profil')) {
            $data['foto_profil'] = $request->file('foto_profil')->store('siswa', 'public');
        }

        $siswa = Siswa::create($data);

        $password = Str::password(10);
        $email = $this->defaultAccountEmail($siswa->nisn);
        $siswa->user()->create([
            'name' => $siswa->nama_lengkap,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'siswa',
            'nisn' => $siswa->nisn,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Peserta didik berhasil ditambahkan. Akun otomatis dibuat — Email: {$email}, Password: {$password}",
        ]);

        return Redirect::route('admin.siswa.index');
    }

    /**
     * Update the specified peserta didik in storage.
     */
    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $data = $this->validateSiswa($request, $siswa);

        if ($request->hasFile('foto_profil')) {
            $data['foto_profil'] = $request->file('foto_profil')->store('siswa', 'public');
        }

        $siswa->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Peserta didik berhasil diperbarui.',
        ]);

        return Redirect::route('admin.siswa.index');
    }

    /**
     * Unduh template XLSX untuk import massal siswa.
     */
    public function template(): BinaryFileResponse
    {
        $path = storage_path('app/templates/template-siswa.xlsx');

        if (! file_exists($path)) {
            $this->buatTemplate($path);
        }

        return response()->download($path, 'template-siswa.xlsx');
    }

    private function buatTemplate(string $path): void
    {
        @mkdir(dirname($path), 0755, true);

        $writer = new \OpenSpout\Writer\XLSX\Writer;
        $writer->openToFile($path);
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(SiswaImportService::HEADER));
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['1234567890', 'Contoh Nama Siswa', 'L', 'Sumedang', '2010-01-01']));
        $writer->close();
    }

    /**
     * Impor massal siswa dari file XLSX.
     */
    public function impor(Request $request, SiswaImportService $service): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ], [], [
            'file' => 'File Excel',
        ]);

        $path = $request->file('file')->getRealPath();
        $hasil = $service->impor($path);

        if ($hasil['gagal'] > 0) {
            Toast::warning("Impor selesai: {$hasil['berhasil']} berhasil, {$hasil['gagal']} gagal. ".implode(' ', array_slice($hasil['errors'], 0, 3)));
        } else {
            Toast::success("Impor selesai: {$hasil['berhasil']} siswa berhasil ditambahkan. Password default = NISN.");
        }

        return Redirect::route('admin.siswa.index');
    }

    /**
     * Remove the specified peserta didik from storage.
     */
    public function destroy(Siswa $siswa): RedirectResponse
    {
        if ($siswa->user || $siswa->siswaKelas()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Peserta didik tidak dapat dihapus karena masih memiliki akun atau kelas terkait.',
            ]);

            return Redirect::route('admin.siswa.index');
        }

        $siswa->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Peserta didik berhasil dihapus.',
        ]);

        return Redirect::route('admin.siswa.index');
    }

    /**
     * Validate the peserta didik request for store/update.
     */
    private function validateSiswa(Request $request, ?Siswa $current = null): array
    {
        return $request->validate([
            'nisn' => ['required', 'string', 'max:10', 'unique:siswa,nisn'.($current ? ','.$current->nisn.',nisn' : '')],
            'nis' => ['required', 'string', 'max:10', 'unique:siswa,nis'.($current ? ','.$current->nis.',nis' : '')],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'alamat' => ['nullable', 'string'],
            'is_aktif' => ['boolean'],
        ], [], [
            'nisn' => 'NISN',
            'nis' => 'NIS',
            'nama_lengkap' => 'Nama Lengkap',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'alamat' => 'Alamat',
            'is_aktif' => 'Aktif',
        ]);
    }

    /**
     * Build the auto-generated account email from the configured template.
     */
    private function defaultAccountEmail(string $nisn): string
    {
        $template = config('services.default_account.email_prefix');

        return str_replace('{nisn}', $nisn, $template);
    }
}
