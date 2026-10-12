@extends('layouts.app')

@section('title', 'Kelola User System')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Akun Pengguna Sistem</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $users->count() }} akun terdaftar</p>
            </div>

            <a href="{{ route('users.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-md transition-colors">
                <i class="fa-solid fa-user-plus"></i>
                Tambah Pengguna
            </a>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="py-3 px-4 text-left font-bold">Username</th>
                        <th class="py-3 px-4 text-left font-bold">Nama Lengkap</th>
                        <th class="py-3 px-4 text-left font-bold">Email</th>
                        <th class="py-3 px-4 text-left font-bold">Peran</th>
                        <th class="py-3 px-4 text-center font-bold">Status</th>
                        <th class="py-3 px-4 text-right font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $user)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4">
                                <span class="font-mono text-xs font-semibold text-slate-700">{{ $user->username }}</span>
                                @if ($user->is(auth()->user()))
                                    <span class="ml-1 text-[10px] font-bold text-amber-600">(Anda)</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-medium text-slate-800">{{ $user->name }}</td>
                            <td class="py-3 px-4 text-xs text-slate-500">{{ $user->email }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-md text-white flex items-center justify-center text-[9px] font-bold {{ $user->role->color() }}">
                                        {{ Str::substr($user->role->value, 0, 3) }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-600">{{ $user->role->label() }}</span>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('users.edit', $user) }}" title="Ubah"
                                   class="inline-flex p-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                @unless ($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline"
                                          data-confirm="Hapus akun {{ $user->username }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus"
                                                class="inline-flex p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
