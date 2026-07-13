@extends('layouts.pos')
@section('title', 'Stock bajo')

@section('content')
<div class="mx-auto max-w-5xl px-8 py-8">
    <header class="mb-2">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Reportes</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Stock bajo</h1>
    </header>

    @include('pos.reports._nav')

    @if ($products->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Ningún producto está por debajo de su stock mínimo</p>
        </div>
    @else
        <div class="rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-3 pl-5 pr-3 font-medium">Producto</th>
                        <th class="px-3 py-3 font-medium">Categoría</th>
                        <th class="px-3 py-3 text-right font-medium">Stock actual</th>
                        <th class="py-3 pl-3 pr-5 text-right font-medium">Mínimo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $p)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="py-2.5 pl-5 pr-3 font-medium">{{ $p->name }}</td>
                            <td class="px-3 py-2.5 text-slate-500">{{ $p->category->name ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums {{ (float) $p->stock === 0.0 ? 'text-rose-600' : 'text-amber-600' }}">
                                {{ rtrim(rtrim(number_format($p->stock, 3), '0'), '.') }} {{ $p->unitOfMeasure->abbreviation ?? '' }}
                            </td>
                            <td class="py-2.5 pl-3 pr-5 text-right tabular-nums text-slate-500">
                                {{ rtrim(rtrim(number_format($p->stock_minimo, 3), '0'), '.') }} {{ $p->unitOfMeasure->abbreviation ?? '' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>
    @endif
</div>
@endsection
