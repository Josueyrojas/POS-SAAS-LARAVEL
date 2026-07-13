@extends('layouts.pos')
@section('title', 'Ventas')

@section('content')
<div class="mx-auto max-w-5xl px-8 py-8">
    <header class="mb-6 flex items-end justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Punto de venta</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ auth()->user()->isAdmin() ? 'Ventas' : 'Mis ventas' }}</h1>
        </div>
        <a href="{{ route('pos.sales.create') }}" class="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            Nueva venta
        </a>
    </header>

    @if ($sales->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Sin ventas todavía</p>
            <p class="mt-1 text-sm text-slate-500">Registra tu primera venta desde el punto de venta.</p>
        </div>
    @else
        <div class="rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-3 pl-5 pr-3 font-medium">Fecha</th>
                        <th class="px-3 py-3 font-medium">Vendedor</th>
                        <th class="px-3 py-3 font-medium">Cliente</th>
                        <th class="px-3 py-3 font-medium">Estado</th>
                        <th class="py-3 pl-3 pr-5 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales as $s)
                        <tr class="cursor-pointer border-b border-slate-100 last:border-0 hover:bg-slate-50"
                            onclick="window.location='{{ route('pos.sales.show', $s->id) }}'">
                            <td class="py-3 pl-5 pr-3 text-slate-500">{{ $s->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-3">{{ $s->seller->name ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-500">{{ $s->customer->name ?? 'Mostrador' }}</td>
                            <td class="px-3 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $s->status->badge() }}">{{ $s->status->label() }}</span>
                            </td>
                            <td class="py-3 pl-3 pr-5 text-right tabular-nums font-medium">${{ number_format($s->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>
        <div class="mt-4">{{ $sales->links() }}</div>
    @endif
</div>
@endsection
