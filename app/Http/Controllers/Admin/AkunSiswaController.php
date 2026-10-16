<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class AkunSiswaController extends Controller
{
    public function show(Siswa $siswa): Response
    {
        $siswa->load('user');

        return Inertia::render('admin/AkunSiswa/AturAkun', [
            'siswa' => $siswa,
            'user' => $siswa->user,
        ]);
    }

    public function store(Request $request, Siswa $siswa): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Password tidak cocok.',
        ]);

        $user = $siswa->user ?? new User;
        $user->name = $siswa->nama_lengkap;
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->role = 'siswa';
        $user->nisn = $siswa->nisn;
        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Akun peserta didik berhasil dibuat.',
        ]);

        return Redirect::route('admin.siswa.index');
    }

    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $siswa->load('user');

        if (! $siswa->user) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Akun peserta didik belum dibuat.',
            ]);

            return Redirect::route('admin.siswa.index');
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$siswa->user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Password tidak cocok.',
        ]);

        $siswa->user->name = $siswa->nama_lengkap;
        $siswa->user->email = $data['email'];

        if (! empty($data['password'])) {
            $siswa->user->password = Hash::make($data['password']);
        }

        $siswa->user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Akun peserta didik berhasil diperbarui.',
        ]);

        return Redirect::route('admin.siswa.index');
    }

    public function destroy(Siswa $siswa): RedirectResponse
    {
        $siswa->load('user');

        if (! $siswa->user) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Akun peserta didik belum ada.',
            ]);

            return Redirect::route('admin.siswa.index');
        }

        $siswa->user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Akun peserta didik berhasil dihapus.',
        ]);

        return Redirect::route('admin.siswa.index');
    }
}
