@extends('layouts.platform')
@section('title', 'Mi perfil')

@section('content')
<div class="mx-auto max-w-md px-8 py-8">
    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Cuenta</p>
    <h1 class="mt-1 text-2xl font-semibold tracking-tight">Mi perfil</h1>

    <form method="POST" action="{{ route('super-admin.profile.update') }}" class="mt-6 space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf @method('PATCH')
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Nombre</label>
            <input name="name" value="{{ old('name', $user->name) }}"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
            @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Correo</label>
            <input name="email" type="email" value="{{ old('email', $user->email) }}"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
            @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="border-t border-slate-200 pt-4">
            <p class="mb-3 text-xs font-medium uppercase tracking-wide text-slate-400">Cambiar contraseña (opcional)</p>
            <div class="space-y-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Contraseña actual</label>
                    <input name="current_password" type="password" autocomplete="current-password"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    @error('current_password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Contraseña nueva</label>
                    <input name="password" type="password" autocomplete="new-password"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Confirmar contraseña nueva</label>
                    <input name="password_confirmation" type="password" autocomplete="new-password"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>
            </div>
        </div>

        <button class="w-full rounded-md bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Guardar cambios
        </button>
    </form>
</div>
@endsection
