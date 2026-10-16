<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfilController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load('siswa', 'guru');

        return Inertia::render('admin/Profil/Index', [
            'profil' => $this->mapProfile($user),
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Profil berhasil diperbarui.',
        ]);

        return Redirect::route('admin.profil.index');
    }

    /**
     * Map the user's profile data for the page.
     */
    private function mapProfile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'google_id' => $user->google_id,
            'created_at' => $user->created_at?->translatedFormat('d F Y'),
            'guru' => $user->guru ? [
                'nip' => $user->guru->nip,
                'nama_lengkap' => $user->guru->nama_lengkap,
                'pendidikan_terakhir' => $user->guru->pendidikan_terakhir,
                'jenis_kelamin' => $user->guru->jenis_kelamin,
                'foto_profil' => $user->guru->foto_profil,
            ] : null,
            'siswa' => $user->siswa ? [
                'nisn' => $user->siswa->nisn,
                'nis' => $user->siswa->nis,
                'nama_lengkap' => $user->siswa->nama_lengkap,
                'kelas' => $user->siswa->kelas?->nama,
                'jenis_kelamin' => $user->siswa->jenis_kelamin,
                'foto_profil' => $user->siswa->foto_profil,
            ] : null,
        ];
    }
}
