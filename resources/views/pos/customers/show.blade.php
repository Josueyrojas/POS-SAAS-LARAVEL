@extends('layouts.pos')
@section('title', $customer->name)

@section('content')
<div class="mx-auto max-w-3xl px-8 py-8" x-data="{ open: false }">
    <a href="{{ route('pos.customers.index') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Clientes</a>

    <header class="mt-3 mb-6 flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $customer->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $customer->phone ?? '—' }} · {{ $customer->email ?? '—' }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Saldo de fiado</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $balance > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                ${{ number_format($balance, 2) }}
            </p>
        </div>
    </header>

    @if ($balance > 0)
        <button @click="open = true" class="mb-6 rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            Registrar abono
        </button>
    @endif

    <section class="mb-8">
        <h2 class="mb-3 text-sm font-medium uppercase tracking-wide text-slate-400">Ventas a crédito</h2>
        @if ($creditSales->isEmpty())
            <p class="text-sm text-slate-400">Sin ventas a crédito todavía.</p>
        @else
            <div class="rounded-lg border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody>
                        @foreach ($creditSales as $s)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-2.5 pl-5 pr-3">
                                    <a href="{{ route('pos.sales.show', $s->id) }}" class="text-indigo-600 hover:text-indigo-500">
                                        {{ $s->created_at->format('d/m/Y H:i') }}
                                    </a>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $s->status->badge() }}">{{ $s->status->label() }}</span>
                                </td>
                                <td class="py-2.5 pl-3 pr-5 text-right tabular-nums font-medium">${{ number_format($s->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        @endif
    </section>

    <section>
        <h2 class="mb-3 text-sm font-medium uppercase tracking-wide text-slate-400">Abonos registrados</h2>
        @if ($payments->isEmpty())
            <p class="text-sm text-slate-400">Sin abonos todavía.</p>
        @else
            <div class="rounded-lg border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody>
                        @foreach ($payments as $p)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-2.5 pl-5 pr-3 text-slate-500">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-2.5 text-slate-500">{{ $p->createdBy->name ?? '—' }}</td>
                                <td class="py-2.5 pl-3 pr-5 text-right tabular-nums font-medium text-emerald-600">${{ number_format($p->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        @endif
    </section>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl" @click.outside="open = false">
            <h2 class="text-sm font-semibold">Registrar abono</h2>
            <p class="mt-1 text-xs text-slate-500">Saldo actual: ${{ number_format($balance, 2) }}</p>
            <form method="POST" action="{{ route('pos.customer-payments.store', $customer->id) }}" class="mt-4">
                @csrf
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Monto</label>
                <input name="amount" type="number" step="0.01" min="0.01" max="{{ $balance }}" value="{{ old('amount') }}"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                @error('amount')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror

                <label class="mb-1.5 mt-3 block text-sm font-medium text-slate-700">Forma de pago</label>
                <select name="payment_method" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    <option value="CASH">Efectivo</option>
                    <option value="CARD">Tarjeta</option>
                    <option value="TRANSFER">Transferencia</option>
                </select>

                <div class="mt-4 flex gap-3">
                    <button type="button" @click="open = false" class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
