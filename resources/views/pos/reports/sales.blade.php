@extends('layouts.pos')
@section('title', 'Reporte de ventas')

@section('content')
<div class="mx-auto max-w-5xl px-8 py-8">
    <header class="mb-2">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Reportes</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Ventas por período</h1>
    </header>

    @include('pos.reports._nav')
    @include('pos.reports._date-range')

    <div class="mb-6 grid grid-cols-3 gap-3">
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total vendido</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">${{ number_format($summary['total'], 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Ventas</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $summary['count'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Ticket promedio</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">${{ number_format($summary['avg_ticket'], 2) }}</p>
        </div>
    </div>

    @if ($rows->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Sin ventas en este período</p>
        </div>
    @else
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <canvas id="salesChart" height="90"></canvas>
        </div>

        <div class="mt-4 rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-3 pl-5 pr-3 font-medium">Día</th>
                        <th class="px-3 py-3 text-center font-medium">Ventas</th>
                        <th class="py-3 pl-3 pr-5 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="py-2.5 pl-5 pr-3">{{ \Illuminate\Support\Carbon::parse($r->day)->format('d/m/Y') }}</td>
                            <td class="px-3 py-2.5 text-center tabular-nums text-slate-500">{{ $r->sales_count }}</td>
                            <td class="py-2.5 pl-3 pr-5 text-right tabular-nums font-medium">${{ number_format($r->total, 2) }}</td>
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
    const ctx = document.getElementById('salesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chart['labels']),
                datasets: [{
                    data: @json($chart['totals']),
                    backgroundColor: '#4f46e5',
                    borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                    borderSkipped: false,
                    maxBarThickness: 24,
                }],
            },
            options: {
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => '$' + c.parsed.y.toFixed(2) } } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { callback: (v) => '$' + v } },
                },
            },
        });
    }
</script>
@endpush
@endsection
