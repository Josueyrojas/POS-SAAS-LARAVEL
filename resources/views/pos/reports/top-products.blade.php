@extends('layouts.pos')
@section('title', 'Productos más vendidos')

@section('content')
<div class="mx-auto max-w-5xl px-8 py-8">
    <header class="mb-2">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Reportes</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Productos más vendidos</h1>
    </header>

    @include('pos.reports._nav')
    @include('pos.reports._date-range')

    @if ($rows->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Sin ventas en este período</p>
        </div>
    @else
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <canvas id="topProductsChart" height="{{ max(120, count($rows) * 36) }}"></canvas>
        </div>

        <div class="mt-4 rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-3 pl-5 pr-3 font-medium">Producto</th>
                        <th class="px-3 py-3 text-right font-medium">Cantidad vendida</th>
                        <th class="py-3 pl-3 pr-5 text-right font-medium">Ingreso</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="py-2.5 pl-5 pr-3 font-medium">{{ $r->name_snapshot }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-500">{{ rtrim(rtrim(number_format($r->qty, 3), '0'), '.') }}</td>
                            <td class="py-2.5 pl-3 pr-5 text-right tabular-nums font-medium">${{ number_format($r->revenue, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const ctx = document.getElementById('topProductsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chart['labels']),
                datasets: [{
                    data: @json($chart['totals']),
                    backgroundColor: '#4f46e5',
                    borderRadius: { topLeft: 0, topRight: 4, bottomLeft: 0, bottomRight: 4 },
                    borderSkipped: false,
                    maxBarThickness: 24,
                }],
            },
            options: {
                indexAxis: 'y',
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => '$' + c.parsed.x.toFixed(2) } } },
                scales: {
                    y: { grid: { display: false } },
                    x: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { callback: (v) => '$' + v } },
                },
            },
        });
    }
</script>
@endpush
@endsection
