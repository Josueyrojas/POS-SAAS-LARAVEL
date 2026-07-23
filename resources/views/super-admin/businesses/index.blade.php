@extends('layouts.platform')
@section('title', 'Negocios')

@php use App\Enums\BusinessStatus; use App\Enums\BusinessPlan; @endphp

@section('content')
<div class="mx-auto max-w-6xl px-8 py-8" x-data="{ open: @json($errors->any() && old('name') !== null) }">
    <header class="mb-8 flex items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Consola de plataforma</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Negocios</h1>
        </div>
        <div class="flex items-center gap-4">
            <div x-data="liveClock()" class="text-right">
                <p class="text-lg font-semibold tabular-nums text-slate-700" x-text="time"></p>
                <p class="text-xs text-slate-400" x-text="date"></p>
            </div>
            <button @click="open = true" class="rounded-md bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-700">
                Nuevo negocio
            </button>
        </div>
    </header>

    {{-- Tira de estado --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <span class="text-xs font-medium uppercase tracking-wide text-slate-400">Total</span>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $metrics['total'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span><span class="text-xs font-medium uppercase tracking-wide text-slate-400">Activos</span></div>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $metrics['active'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span><span class="text-xs font-medium uppercase tracking-wide text-slate-400">Pendientes</span></div>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $metrics['pending'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span><span class="text-xs font-medium uppercase tracking-wide text-slate-400">Suspendidos</span></div>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $metrics['suspended'] }}</p>
        </div>
    </div>

    @if ($businesses->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Aún no hay negocios</p>
            <p class="mt-1 text-sm text-slate-500">Da de alta el primer negocio para empezar a operar.</p>
        </div>
    @else
        <div class="rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-3 pl-5 pr-3 font-medium">Negocio</th>
                        <th class="px-3 py-3 font-medium">Estado</th>
                        <th class="px-3 py-3 font-medium">Plan</th>
                        <th class="px-3 py-3 text-right font-medium">Usuarios</th>
                        <th class="px-3 py-3 text-right font-medium">Productos</th>
                        <th class="px-3 py-3 text-right font-medium">Ventas</th>
                        <th class="py-3 pl-3 pr-5 text-right font-medium">Cambiar estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($businesses as $b)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                            <td class="relative py-3 pl-5 pr-3">
                                <span class="absolute inset-y-0 left-0 w-1 {{ $b->status->accent() }}"></span>
                                <a href="{{ route('super-admin.businesses.show', $b->id) }}" class="font-medium hover:underline">{{ $b->name }}</a>
                                <p class="text-xs text-slate-400">/{{ $b->slug }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $b->status->badge() }}">
                                    {{ $b->status->label() }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <form method="POST" action="{{ route('super-admin.businesses.plan', $b->id) }}">
                                    @csrf @method('PATCH')
                                    <select name="plan" onchange="this.form.submit()" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs">
                                        @foreach (BusinessPlan::cases() as $plan)
                                            <option value="{{ $plan->value }}" @selected($b->plan === $plan)>{{ $plan->label() }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-600">{{ $b->users_count }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-600">{{ $b->products_count }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-600">{{ $b->sales_count }}</td>
                            <td class="py-3 pl-3 pr-5 text-right">
                                <form method="POST" action="{{ route('super-admin.businesses.status', $b->id) }}">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs">
                                        @foreach (BusinessStatus::cases() as $st)
                                            <option value="{{ $st->value }}" @selected($b->status === $st)>{{ $st->label() }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>
    @endif

    {{-- Modal de alta (panel deslizante) --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex justify-end bg-slate-900/30">
        <div class="flex h-full w-full max-w-md flex-col bg-white shadow-xl" @click.outside="open = false">
            <div class="flex h-14 items-center justify-between border-b border-slate-200 px-6">
                <h2 class="text-sm font-semibold">Alta de negocio</h2>
                <button @click="open = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form method="POST" action="{{ route('super-admin.businesses.store') }}" class="flex flex-1 flex-col">
                @csrf
                <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Nombre del negocio</label>
                        <input name="name" value="{{ old('name') }}" placeholder="Cafetería Central"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Plan</label>
                        <select name="plan" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            @foreach (BusinessPlan::cases() as $plan)
                                <option value="{{ $plan->value }}">{{ $plan->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="border-t border-slate-100 pt-5">
                        <p class="mb-3 text-xs font-medium uppercase tracking-wide text-slate-400">Administrador del negocio</p>
                        <div class="space-y-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Nombre</label>
                                <input name="admin_name" value="{{ old('admin_name') }}" placeholder="María López"
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                                @error('admin_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Correo</label>
                                <input name="admin_email" type="email" value="{{ old('admin_email') }}" placeholder="admin@negocio.com"
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                                @error('admin_email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <p class="rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-500">
                                Se le enviará un correo para que defina su propia contraseña.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 border-t border-slate-200 px-6 py-4">
                    <button type="button" @click="open = false" class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button class="flex-1 rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700">Crear negocio</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
