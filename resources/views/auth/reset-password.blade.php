@extends('layouts.public')
@section('title', 'Nueva contraseña')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-6">
                <h1 class="text-lg font-semibold">Nueva contraseña</h1>
                <p class="mt-1 text-sm text-slate-500">Elige una contraseña nueva de al menos 8 caracteres.</p>
            </div>

            <form method="POST" action="{{ $postUrl }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Contraseña nueva</label>
                    <input name="password" type="password" autocomplete="new-password" autofocus
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Confirmar contraseña</label>
                    <input name="password_confirmation" type="password" autocomplete="new-password"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>
                @error('password')
                    <p class="rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
                <button class="w-full rounded-md bg-slate-900 px-3 py-2.5 text-sm font-medium text-white hover:bg-slate-700">
                    Guardar nueva contraseña
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
