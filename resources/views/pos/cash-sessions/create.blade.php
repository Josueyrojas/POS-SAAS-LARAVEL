@extends('layouts.pos')
@section('title', 'Abrir turno de caja')

@section('content')
<div class="mx-auto max-w-sm px-8 py-16">
    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Caja</p>
    <h1 class="mt-1 text-2xl font-semibold tracking-tight">Abrir turno</h1>
    <p class="mt-1 text-sm text-slate-500">Captura el fondo inicial en efectivo antes de empezar a vender.</p>

    <form method="POST" action="{{ route('pos.cash-sessions.store') }}" class="mt-6 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Fondo inicial</label>
        <input name="opening_amount" type="number" step="0.01" min="0" value="{{ old('opening_amount', '0') }}" autofocus
               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
        @error('opening_amount')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror

        <button class="mt-5 w-full rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            Abrir turno
        </button>
    </form>
</div>
@endsection
