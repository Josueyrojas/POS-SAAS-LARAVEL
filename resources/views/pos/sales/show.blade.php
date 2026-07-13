@extends('layouts.pos')
@section('title', 'Venta')

@section('content')
@php
    // Un solo @json() por atributo: dos o más anidados truncan la expresión
    // (Blade) y dejan a Alpine sin poder inicializar el componente.
    $initConfig = ['voidUrl' => route('pos.sales.void', $sale->id), 'refundUrl' => route('pos.sales.refund', $sale->id)];
@endphp
<div class="mx-auto max-w-2xl px-8 py-8" x-data="{ reasonOpen: null, ...{{ Illuminate\Support\Js::from($initConfig) }} }">
    <div class="no-print flex items-center justify-between">
        <a href="{{ route('pos.sales.index') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Volver a ventas</a>
        <button onclick="window.print()" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Imprimir
        </button>
    </div>

    <header class="mt-3 mb-6 flex items-start justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Venta</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $sale->created_at->format('d/m/Y H:i') }}</h1>
        </div>
        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $sale->status->badge() }}">{{ $sale->status->label() }}</span>
    </header>

    @php($business = auth()->user()->business)
    <div id="receipt-ticket" class="rounded-lg border border-slate-200 bg-white p-6">
        <div class="mb-4 text-center">
            @if ($business?->logo_url)
                <img src="{{ $business->logo_url }}" alt="" class="mx-auto mb-2 max-h-16">
            @endif
            <p class="text-sm font-semibold">{{ $business->name ?? '' }}</p>
            @if ($business?->address)
                <p class="text-xs text-slate-500">{{ $business->address }}</p>
            @endif
            @if ($business?->phone)
                <p class="text-xs text-slate-500">{{ $business->phone }}</p>
            @endif
        </div>

        <div class="mb-4 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs text-slate-400">Vendedor</p>
                <p class="font-medium">{{ $sale->seller->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400">Cliente</p>
                <p class="font-medium">{{ $sale->customer->name ?? 'Mostrador' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400">Método de pago</p>
                <p class="font-medium">{{ $sale->payment_method?->label() ?? '—' }}</p>
            </div>
            @if ($sale->payment_method?->value === 'CASH' && $sale->amount_tendered !== null)
                <div>
                    <p class="text-xs text-slate-400">Recibido / cambio</p>
                    <p class="font-medium">${{ number_format($sale->amount_tendered, 2) }} / ${{ number_format($sale->change_due, 2) }}</p>
                </div>
            @endif
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-y border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                    <th class="py-2 font-medium">Producto</th>
                    <th class="py-2 text-right font-medium">Cant.</th>
                    <th class="py-2 text-right font-medium">P. unit.</th>
                    <th class="py-2 text-right font-medium">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="py-2.5">
                            {{ $item->name_snapshot }}
                            @if ($item->price_type->value === 'WHOLESALE')
                                <span class="ml-1 rounded bg-emerald-50 px-1.5 py-0.5 text-xs text-emerald-700">Mayoreo</span>
                            @endif
                        </td>
                        <td class="py-2.5 text-right tabular-nums text-slate-500">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }} {{ $item->unit_label_snapshot }}</td>
                        <td class="py-2.5 text-right tabular-nums text-slate-500">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-2.5 text-right tabular-nums font-medium">${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 space-y-1 border-t border-slate-200 pt-3 text-sm">
            <div class="flex items-center justify-between text-slate-500">
                <span>Subtotal</span>
                <span class="tabular-nums">${{ number_format($sale->items_subtotal ?? $sale->total, 2) }}</span>
            </div>
            @if ($sale->discount_amount > 0)
                <div class="flex items-center justify-between text-slate-500">
                    <span>Descuento @if($sale->discount_type?->value === 'PERCENT')({{ rtrim(rtrim(number_format($sale->discount_value, 2), '0'), '.') }}%)@endif</span>
                    <span class="tabular-nums">-${{ number_format($sale->discount_amount, 2) }}</span>
                </div>
            @endif
            <div class="flex items-center justify-between border-t border-slate-100 pt-2">
                <span class="font-medium text-slate-600">Total</span>
                <span class="text-xl font-semibold tabular-nums">${{ number_format($sale->total, 2) }}</span>
            </div>
            @if ($sale->tax_amount !== null)
                <p class="text-xs text-slate-400">Incluye IVA ({{ rtrim(rtrim(number_format($sale->tax_rate, 2), '0'), '.') }}%): ${{ number_format($sale->tax_amount, 2) }}</p>
            @endif
        </div>

        @if ($business?->receipt_footer)
            <p class="mt-4 border-t border-slate-100 pt-3 text-center text-xs text-slate-500">{{ $business->receipt_footer }}</p>
        @endif
    </div>

    @if ($sale->status->value === 'VOIDED')
        <p class="mt-4 text-sm text-slate-500">Anulada por {{ $sale->voidedBy->name ?? '—' }} el {{ $sale->voided_at?->format('d/m/Y H:i') }}
            @if ($sale->void_reason) — {{ $sale->void_reason }} @endif</p>
    @elseif ($sale->status->value === 'REFUNDED')
        <p class="mt-4 text-sm text-slate-500">Reembolsada por {{ $sale->refundedBy->name ?? '—' }} el {{ $sale->refunded_at?->format('d/m/Y H:i') }}
            @if ($sale->refund_reason) — {{ $sale->refund_reason }} @endif</p>
    @elseif (auth()->user()->isAdmin())
        <div class="no-print mt-4 flex gap-3">
            <button @click="reasonOpen = 'void'" class="rounded-md border border-slate-300 px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Anular</button>
            <button @click="reasonOpen = 'refund'" class="rounded-md border border-rose-300 px-3.5 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">Reembolsar</button>
        </div>

        <div x-show="reasonOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30">
            <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl" @click.outside="reasonOpen = null">
                <h2 class="text-sm font-semibold" x-text="reasonOpen === 'void' ? 'Anular venta' : 'Reembolsar venta'"></h2>
                <p class="mt-1 text-xs text-slate-500">El stock de los productos se restituye automáticamente.</p>
                <form method="POST" :action="reasonOpen === 'void' ? voidUrl : refundUrl" class="mt-4">
                    @csrf @method('PATCH')
                    <textarea name="reason" rows="2" placeholder="Motivo (opcional)"
                              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></textarea>
                    <div class="mt-4 flex gap-3">
                        <button type="button" @click="reasonOpen = null" class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button class="flex-1 rounded-md bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-500">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
