@extends('layouts.public')
@section('title', 'Recuperar contraseña')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-6">
                <h1 class="text-lg font-semibold">Recuperar contraseña</h1>
                <p class="mt-1 text-sm text-slate-500">
                    @if (isset($business))
                        Te enviaremos un enlace para restablecerla en {{ $business->name }}.
                    @else
                        Te enviaremos un enlace para restablecer tu acceso de plataforma.
                    @endif
                </p>
            </div>

            @if (session('status'))
                <p class="mb-4 rounded-md bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ $postUrl }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Correo</label>
                    <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" autofocus
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button class="w-full rounded-md bg-slate-900 px-3 py-2.5 text-sm font-medium text-white hover:bg-slate-700">
                    Enviar enlace
                </button>
            </form>

            <a href="{{ isset($business) ? route('business.login', $business->slug) : route('login') }}"
               class="mt-6 block text-center text-sm text-slate-500 hover:text-slate-900">← Volver al inicio de sesión</a>
        </div>
    </div>
</div>
@endsection
