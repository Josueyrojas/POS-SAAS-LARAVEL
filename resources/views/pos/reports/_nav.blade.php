@php
    $tabs = [
        ['pos.reports.sales', 'Ventas'],
        ['pos.reports.top-products', 'Productos más vendidos'],
        ['pos.reports.margin', 'Margen'],
        ['pos.reports.low-stock', 'Stock bajo'],
    ];
@endphp
<div class="mb-6 flex gap-1 border-b border-slate-200">
    @foreach ($tabs as [$route, $label])
        <a href="{{ route($route) }}"
           class="border-b-2 px-3 py-2 text-sm font-medium {{ request()->routeIs($route) ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
