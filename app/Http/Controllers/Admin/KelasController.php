<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class KelasController extends Controller
{
    public function index()
    {
        $kelas = Kelas::with(['jurusan', 'walikelas'])->withCount('siswas')->get();

        $buildTree = function ($parentId, $depth = 0) use ($kelas, &$buildTree) {
            return $kelas
                ->where('parent_id', $parentId)
                ->sortBy('nama')
                ->map(function ($item) use ($buildTree, $depth) {
                    $node = $item->toArray();
                    $node['depth'] = $depth;
                    $node['siswa_count'] = $item->siswas_count;
                    $node['children'] = $buildTree($item->id, $depth + 1);

                    return $node;
                })
                ->values()
                ->all();
        };

        $depthOf = [];
        $getDepth = function (Kelas $k) use (&$getDepth, $kelas, &$depthOf) {
            if (isset($depthOf[$k->id])) {
                return $depthOf[$k->id];
            }
            if ($k->parent_id === null) {
                return $depthOf[$k->id] = 0;
            }
            $parent = $kelas->firstWhere('id', $k->parent_id);

            return $depthOf[$k->id] = $parent ? $getDepth($parent) + 1 : 0;
        };

        return inertia('admin/Kelas/Index', [
            'kelas_parent' => $buildTree(null),
            'kelas_list' => $kelas->map(fn(Kelas $k) => [
                'id' => $k->id,
                'nama' => $k->nama,
                'parent_id' => $k->parent_id,
                'jurusan_id' => $k->jurusan_id,
                'depth' => $getDepth($k),
            ]),
            'jurusans' => Jurusan::orderBy('name')->get(['id', 'name', 'kode']),
            'gurus' => Guru::orderBy('nama_lengkap')->get(['id', 'nama_lengkap']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateKelas($request);

        Kelas::create($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kelas berhasil ditambahkan.',
        ]);

        return Redirect::route('admin.kelas.index');
    }

    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        $data = $this->validateKelas($request, $kelas);

        $kelas->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kelas berhasil diperbarui.',
        ]);

        return Redirect::route('admin.kelas.index');
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        $ids = $this->descendantIds($kelas);

        if (Kelas::whereIn('id', $ids)->has('siswas')->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Kelas tidak dapat dihapus karena masih memiliki siswa.',
            ]);

            return Redirect::route('admin.kelas.index');
        }

        $childIds = array_slice($ids, 1);
        if ($childIds) {
            Kelas::whereIn('id', $childIds)->delete();
        }
        $kelas->delete();

        $total = count($ids);
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $total > 1
                ? "Kelas beserta {$total} kelas terkait berhasil dihapus."
                : 'Kelas berhasil dihapus.',
        ]);

        return Redirect::route('admin.kelas.index');
    }

    private function validateKelas(Request $request, ?Kelas $kelas = null): array
    {
        $request->merge(collect(['jurusan_id', 'guru_id', 'parent_id'])
            ->mapWithKeys(function (string $key) use ($request) {
                $value = $request->input($key);

                if ($value === '' || $value === null) {
                    return [$key => null];
                }

                if (is_array($value) || is_object($value)) {
                    $value = is_array($value)
                        ? reset($value)
                        : ($value->value ?? null);
                }

                return [$key => $value];
            })
            ->all());

        $request->merge([
            'deskripsi' => $request->input('deskripsi') ?? '',
        ]);

        $parentId = $request->input('parent_id');

        return $request->validate([
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kelas', 'nama')
                    ->where(function ($query) use ($parentId) {
                        if ($parentId === null) {
                            $query->whereNull('parent_id');
                        } else {
                            $query->where('parent_id', $parentId);
                        }
                    })
                    ->ignore($kelas?->id),
            ],
            'deskripsi' => ['nullable', 'string'],
            'jurusan_id' => ['nullable', 'exists:jurusans,id'],
            'guru_id' => ['nullable', 'exists:gurus,id'],
            'parent_id' => [
                'nullable',
                'exists:kelas,id',
                ...($kelas !== null ? [Rule::notIn($this->descendantIds($kelas))] : []),
            ],
            'active' => ['boolean'],
        ], [], [
            'nama' => 'Nama Kelas',
            'deskripsi' => 'Deskripsi',
            'jurusan_id' => 'Jurusan',
            'guru_id' => 'Wali Kelas',
            'parent_id' => 'Kelas Induk',
            'active' => 'Aktif',
        ]);
    }

    /**
     * Id kelas ini beserta seluruh turunannya (untuk guard & penghapusan berantai).
     */
    private function descendantIds(Kelas $kelas): array
    {
        $ids = [$kelas->id];
        $current = [$kelas->id];

        while ($current) {
            $current = Kelas::whereIn('parent_id', $current)->pluck('id')->all();
            $ids = array_merge($ids, $current);
        }

        return $ids;
    }
}
