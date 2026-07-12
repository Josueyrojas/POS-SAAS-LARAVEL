<!DOCTYPE html>
<html lang="es">
<head>@include('partials.head')</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<div class="flex min-h-screen">
    <aside class="flex w-60 shrink-0 flex-col border-r border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Negocio</p>
            <p class="mt-0.5 truncate text-sm font-semibold">{{ auth()->user()->business->name ?? '—' }}</p>
        </div>
        <nav class="flex-1 p-3">
            @php($nav = [
                ['pos.sales.create', 'Vender', 'pos.sales.create'],
                ['pos.sales.index', 'Ventas', 'pos.sales.index|pos.sales.show'],
                ['pos.cash-sessions.show', 'Caja', 'pos.cash-sessions.show|pos.cash-sessions.create'],
                ['pos.dashboard', 'Panel', 'pos.dashboard'],
                ['pos.products.index', 'Productos', 'pos.products.*'],
                ['pos.customers.index', 'Clientes', 'pos.customers.*'],
            ])
            @php($adminNav = [
                ['pos.categories.index', 'Categorías', 'pos.categories.*'],
                ['pos.suppliers.index', 'Proveedores', 'pos.suppliers.*'],
                ['pos.purchases.index', 'Compras', 'pos.purchases.*'],
                ['pos.employees.index', 'Empleados', 'pos.employees.*'],
                ['pos.cash-sessions.index', 'Cortes de caja', 'pos.cash-sessions.index'],
                ['pos.reports.sales', 'Reportes', 'pos.reports.*'],
            ])
            @foreach ($nav as [$route, $label, $pattern])
                <a href="{{ route($route) }}"
                   class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs(...explode('|', $pattern)) ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                </a>
            @endforeach
            @can('manage-products')
                <p class="mt-4 px-3 text-xs font-medium uppercase tracking-wide text-slate-400">Administración</p>
                @foreach ($adminNav as [$route, $label, $pattern])
                    <a href="{{ route($route) }}"
                       class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs(...explode('|', $pattern)) ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        {{ $label }}
                    </a>
                @endforeach
            @endcan
        </nav>
        <div class="border-t border-slate-200 px-5 py-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-xs text-slate-500 hover:text-slate-900">Salir</button>
            </form>
        </div>
    </aside>
    <main class="flex-1 bg-slate-50">
        @if (session('status'))
            <div class="mx-auto max-w-5xl px-8 pt-4">
                <div class="rounded-md bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</div>
            </div>
        @endif
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
