@extends('layouts.pos')
@section('title', 'Panel')

@section('content')
<div class="mx-auto max-w-4xl px-8 py-8">
    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Punto de venta</p>
    <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $business->name }}</h1>

    <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span><span class="text-xs font-medium uppercase tracking-wide text-slate-400">Productos activos</span></div>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $stats['active'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span><span class="text-xs font-medium uppercase tracking-wide text-slate-400">Stock bajo</span></div>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $stats['low'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span><span class="text-xs font-medium uppercase tracking-wide text-slate-400">Agotados</span></div>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $stats['out'] }}</p>
        </div>
    </div>

    <a href="{{ route('pos.products.index') }}" class="mt-6 inline-block rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">
        Gestionar inventario
    </a>

    @if ($lowStockProducts->isNotEmpty())
        <div class="mt-8">
            <h2 class="mb-3 text-sm font-semibold text-slate-700">Alertas de stock bajo</h2>
            <div class="rounded-lg border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody>
                        @foreach ($lowStockProducts as $p)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-2.5 pl-5 pr-3 font-medium">{{ $p->name }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums {{ (float) $p->stock === 0.0 ? 'text-rose-600' : 'text-amber-600' }}">
                                    {{ rtrim(rtrim(number_format($p->stock, 3), '0'), '.') }} / mín. {{ rtrim(rtrim(number_format($p->stock_minimo, 3), '0'), '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    @endif

    @if ($salesChart)
        <div class="mt-8">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Ventas — últimos 7 días</h2>
                <a href="{{ route('pos.reports.sales') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">Ver reportes &rarr;</a>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <canvas id="dashboardSalesChart" height="80"></canvas>
            </div>
        </div>

        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
        <script>
            new Chart(document.getElementById('dashboardSalesChart'), {
                type: 'bar',
                data: {
                    labels: @json($salesChart['labels']),
                    datasets: [{
                        data: @json($salesChart['totals']),
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
        </script>
        @endpush
    @endif
</div>
@endsection
