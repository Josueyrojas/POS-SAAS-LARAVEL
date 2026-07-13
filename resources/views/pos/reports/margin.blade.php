@extends('layouts.pos')
@section('title', 'Margen')

@section('content')
<div class="mx-auto max-w-5xl px-8 py-8">
    <header class="mb-2">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Reportes</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Margen por producto</h1>
    </header>

    @include('pos.reports._nav')
    @include('pos.reports._date-range')

    <div class="mb-6 grid grid-cols-3 gap-3">
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Ingreso</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">${{ number_format($summary['revenue'], 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Costo</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">${{ number_format($summary['cost'], 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Margen</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $summary['margin'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">${{ number_format($summary['margin'], 2) }}</p>
        </div>
    </div>

    @if ($rows->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Sin ventas en este período</p>
        </div>
    @else
        <div class="rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-3 pl-5 pr-3 font-medium">Producto</th>
                        <th class="px-3 py-3 text-right font-medium">Ingreso</th>
                        <th class="px-3 py-3 text-right font-medium">Costo</th>
                        <th class="py-3 pl-3 pr-5 text-right font-medium">Margen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="py-2.5 pl-5 pr-3 font-medium">{{ $r->name }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-500">${{ number_format($r->revenue, 2) }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-500">
                                {{ $r->has_cost ? '$'.number_format($r->cost, 2) : '—' }}
                            </td>
                            <td class="py-2.5 pl-3 pr-5 text-right tabular-nums font-medium {{ $r->margin >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                ${{ number_format($r->margin, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>
        <p class="mt-2 text-xs text-slate-400">El costo usa el precio de costo actual del producto (último costo de compra registrado); no se congela por venta.</p>
    @endif
</div>
@endsection
