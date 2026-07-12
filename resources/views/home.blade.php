@extends('layouts.public')
@section('title', 'Selecciona tu negocio')

@section('content')
<div class="min-h-screen">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-900 text-xs font-bold text-white">P</span>
                <span class="text-sm font-semibold tracking-tight">POS Plataforma</span>
            </div>
            <a href="{{ route('login') }}"
               class="rounded-md border border-slate-300 px-3.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
                Iniciar sesión
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-6 py-14" x-data="{ q: '' }">
        <div class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight">Selecciona tu negocio</h1>
            <p class="mt-1.5 text-sm text-slate-500">Elige tu negocio para entrar a su punto de venta.</p>
        </div>

        @if ($businesses->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white py-16 text-center">
                <p class="text-sm font-medium">No hay negocios disponibles todavía</p>
                <p class="mt-1 text-sm text-slate-500">Vuelve pronto o contacta al administrador de la plataforma.</p>
            </div>
        @else
            <input x-model="q" type="text" placeholder="Busca tu negocio…"
                   class="mb-5 w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none">

            <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($businesses as $b)
                    <li x-show="q === '' || '{{ Str::lower($b->name) }}'.includes(q.toLowerCase())">
                        <a href="{{ route('business.login', $b->slug) }}"
                           class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 hover:border-indigo-300 hover:shadow-sm">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-sm font-bold text-indigo-600">
                                {{ Str::upper(Str::substr($b->name, 0, 1)) }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium">{{ $b->name }}</span>
                                <span class="block truncate text-xs text-slate-400">/{{ $b->slug }}</span>
                            </span>
                            <span class="text-slate-300 group-hover:text-indigo-500">→</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </main>
</div>
@endsection
