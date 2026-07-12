@extends('layouts.public')
@section('title', $business->name)

@section('content')
<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-6">
                <div class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">
                    {{ Str::upper(Str::substr($business->name, 0, 1)) }}
                </div>
                <h1 class="text-lg font-semibold">{{ $business->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">Inicia sesión en tu punto de venta.</p>
            </div>

            <form method="POST" action="{{ route('business.login', $business->slug) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Correo</label>
                    <input name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Contraseña</label>
                    <input name="password" type="password" autocomplete="current-password"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                </div>
                @error('email')
                    <p class="rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
                <button class="w-full rounded-md bg-indigo-600 px-3 py-2.5 text-sm font-medium text-white hover:bg-indigo-500">
                    Iniciar sesión
                </button>
            </form>

            <a href="{{ route('home') }}" class="mt-6 block text-center text-sm text-slate-500 hover:text-slate-900">← Elegir otro negocio</a>
        </div>
    </div>
</div>
@endsection
