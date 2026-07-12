@extends('layouts.pos')
@section('title', 'Mi turno de caja')

@section('content')
<div class="mx-auto max-w-lg px-8 py-8" x-data="{ closing: false }">
    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Caja</p>
    <h1 class="mt-1 text-2xl font-semibold tracking-tight">Turno abierto</h1>
    <p class="mt-1 text-sm text-slate-500">Desde {{ $session->opening_at->format('d/m/Y H:i') }}</p>

    <div class="mt-6 grid grid-cols-2 gap-3">
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Fondo inicial</p>
            <p class="mt-1 text-xl font-semibold tabular-nums">${{ number_format($session->opening_amount, 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Efectivo esperado ahora</p>
            <p class="mt-1 text-xl font-semibold tabular-nums">${{ number_format($expected, 2) }}</p>
        </div>
    </div>

    <div class="mt-6 flex gap-3">
        <a href="{{ route('pos.sales.create') }}" class="flex-1 rounded-md bg-indigo-600 px-3.5 py-2 text-center text-sm font-medium text-white hover:bg-indigo-500">
            Ir a vender
        </a>
        <button @click="closing = true" class="flex-1 rounded-md border border-slate-300 px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Cerrar turno
        </button>
    </div>

    <div x-show="closing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl" @click.outside="closing = false">
            <h2 class="text-sm font-semibold">Cerrar turno</h2>
            <p class="mt-1 text-xs text-slate-500">Cuenta el efectivo real en caja y captúralo abajo.</p>
            <form method="POST" action="{{ route('pos.cash-sessions.close') }}" class="mt-4"
                  x-data="{ amount: null, expected: {{ $expected }} }">
                @csrf
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Efectivo contado</label>
                <input name="closing_amount" x-model.number="amount" type="number" step="0.01" min="0" autofocus
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                @error('closing_amount')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror

                <p class="mt-2 text-sm" x-show="amount !== null">
                    Diferencia:
                    <span :class="(amount - expected) < 0 ? 'text-rose-600' : 'text-emerald-600'"
                          x-text="'$' + (amount - expected).toFixed(2)"></span>
                </p>

                <div class="mt-4 flex gap-3">
                    <button type="button" @click="closing = false" class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button class="flex-1 rounded-md bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-500">Confirmar cierre</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
