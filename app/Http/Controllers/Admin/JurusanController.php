<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class JurusanController extends Controller
{
    /**
     * Display a listing of the jurusan.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $hasKelas = $request->query('has_kelas');

        $query = Jurusan::query();
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        }
        if ($hasKelas === '1') {
            $query->has('kelas');
        } elseif ($hasKelas === '0') {
            $query->doesntHave('kelas');
        }

        $jurusans = $query->withCount(['kelas as jumlah_kelas' => fn ($q) => $q->leaf()])
            ->orderBy('name')
            ->paginate(10)
            ->through(fn (Jurusan $jurusan) => [
                'id' => $jurusan->id,
                'name' => $jurusan->name,
                'kode' => $jurusan->kode,
                'jumlah_kelas' => $jurusan->jumlah_kelas,
            ]);

        return Inertia::render('admin/Jurusan/Index', [
            'jurusans' => Inertia::merge($jurusans),
            'filters' => [
                'search' => $search ?? '',
                'has_kelas' => $hasKelas ?? '',
            ],
        ]);
    }

    /**
     * Store a newly created jurusan in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:255', 'unique:jurusans,kode'],
        ], [], [
            'name' => 'Nama Jurusan',
            'kode' => 'Kode Jurusan',
        ]);

        Jurusan::create($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Jurusan berhasil ditambahkan.',
        ]);

        return Redirect::route('admin.jurusan.index');
    }

    /**
     * Update the specified jurusan in storage.
     */
    public function update(Request $request, Jurusan $jurusan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:255', 'unique:jurusans,kode,'.$jurusan->id],
        ], [], [
            'name' => 'Nama Jurusan',
            'kode' => 'Kode Jurusan',
        ]);

        $jurusan->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Jurusan berhasil diperbarui.',
        ]);

        return Redirect::route('admin.jurusan.index');
    }

    /**
     * Remove the specified jurusan from storage.
     */
    public function destroy(Jurusan $jurusan): RedirectResponse
    {
        $jurusan->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Jurusan berhasil dihapus.',
        ]);

        return Redirect::route('admin.jurusan.index');
    }
}
