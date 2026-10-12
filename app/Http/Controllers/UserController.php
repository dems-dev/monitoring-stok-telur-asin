<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('role:ADMIN')];
    }

    public function index(): View
    {
        return view('users.index', [
            'users' => User::orderBy('username')->get(),
        ]);
    }

    public function create(): View
    {
        return view('users.form', [
            'user' => new User(),
            'roles' => Role::cases(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        User::create($data);

        return redirect()
            ->route('users.index')
            ->with('status', 'Pengguna baru berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('users.form', [
            'user' => $user,
            'roles' => Role::cases(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Password kosong berarti tidak diubah.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $isActive = $request->boolean('is_active', true);

        // Admin tidak boleh mengubah peran atau menonaktifkan akun dirinya sendiri.
        if ($user->is($request->user())) {
            $data['role'] = $user->role->value;
            $isActive = true;
        }

        // Jangan izinkan menonaktifkan admin terakhir yang masih aktif.
        if ($user->isAdmin() && ! $isActive && User::where('role', Role::ADMIN)->where('is_active', true)->where('id', '!=', $user->id)->count() === 0) {
            return back()->withErrors(['is_active' => 'Sistem harus menyisakan minimal satu akun admin yang aktif.']);
        }

        $data['is_active'] = $isActive;

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('status', "Akun {$user->username} berhasil diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(request()->user())) {
            return back()->withErrors(['user' => 'Anda tidak bisa menghapus akun yang sedang dipakai.']);
        }

        if ($user->isAdmin() && User::where('role', Role::ADMIN)->count() <= 1) {
            return back()->withErrors(['user' => 'Sistem harus menyisakan minimal satu akun admin.']);
        }

        $username = $user->username;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', "Akun {$username} dihapus.");
    }
}
