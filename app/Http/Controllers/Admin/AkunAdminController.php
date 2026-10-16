<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class AkunAdminController extends Controller
{
    /**
     * Display a listing of admin accounts.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');

        // CBT v2: kelola akun admin & panitia ujian.
        // Eager load guru/siswa karena accessor `role` membacanya (lazy load dimatikan).
        $query = User::with(['guru:id', 'siswa:id'])->whereIn('role', ['admin', 'panitia']);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $akunAdmin = $query->orderBy('name')
            ->paginate(10)
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at?->translatedFormat('d M Y'),
            ]);

        return Inertia::render('admin/AkunAdmin/Index', [
            'akunAdmin' => Inertia::merge($akunAdmin),
            'filters' => [
                'search' => $search ?? '',
            ],
        ]);
    }

    /**
     * Store a newly created admin account in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,panitia'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_admin' => $data['role'] === 'admin',
        ]);

        $label = $data['role'] === 'panitia' ? 'panitia ujian' : 'admin';
        Toast::success("Akun {$label} berhasil ditambahkan.");

        return Redirect::route('admin.akun-admin.index');
    }

    /**
     * Update the specified admin account in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $user->is_admin) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Akun ini bukan akun admin.',
            ]);

            return Redirect::route('admin.akun-admin.index');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Akun admin berhasil diperbarui.',
        ]);

        return Redirect::route('admin.akun-admin.index');
    }

    /**
     * Remove the specified admin account from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if (! $user->is_admin) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Akun ini bukan akun admin.',
            ]);

            return Redirect::route('admin.akun-admin.index');
        }

        if ($user->id === $request->user()?->id) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Anda tidak dapat menghapus akun admin yang sedang digunakan.',
            ]);

            return Redirect::route('admin.akun-admin.index');
        }

        if (User::where('role', 'admin')->count() <= 1) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Tidak dapat menghapus akun admin terakhir.',
            ]);

            return Redirect::route('admin.akun-admin.index');
        }

        $user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Akun admin berhasil dihapus.',
        ]);

        return Redirect::route('admin.akun-admin.index');
    }
}
