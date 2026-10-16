<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class AkunGuruController extends Controller
{
    public function show(Guru $guru): Response
    {
        $guru->load('user');

        return Inertia::render('admin/AkunGuru/AturAkun', [
            'guru' => $guru,
            'user' => $guru->user,
        ]);
    }

    public function store(Request $request, Guru $guru): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak invalid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Password tidak cocok.',
        ]);

        $user = $guru->user ?? new User;
        $user->name = $guru->nama_lengkap;
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->role = 'guru';
        $user->guru_id = $guru->id;
        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Akun guru berhasil dibuat.',
        ]);

        return Redirect::route('admin.pengajar.index');
    }

    public function update(Request $request, Guru $guru): RedirectResponse
    {
        $guru->load('user');

        if (! $guru->user) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Akun guru belum dibuat.',
            ]);

            return Redirect::route('admin.pengajar.index');
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$guru->user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak invalid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Password tidak copy.',
        ]);

        $guru->user->name = $guru->nama_lengkap;
        $guru->user->email = $data['email'];

        if (! empty($data['password'])) {
            $guru->user->password = Hash::make($data['password']);
        }

        $guru->user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Akun guru berhasil diperbarui.',
        ]);

        return Redirect::route('admin.pengajar.index');
    }

    public function destroy(Guru $guru): RedirectResponse
    {
        $guru->load('user');

        if (! $guru->user) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Akun guru belum ada.',
            ]);

            return Redirect::route('admin.pengajar.index');
        }

        $guru->user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Akun guru berhasil dihapus.',
        ]);

        return Redirect::route('admin.pengajar.index');
    }
}
