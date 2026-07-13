<!DOCTYPE html>
<html lang="es">
<head>@include('partials.head')</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased" x-data="{ sidebarOpen: false }">
<div class="flex min-h-screen">
    {{-- Barra superior: solo visible en móvil (&lt; lg), da acceso al menú. --}}
    <div class="fixed inset-x-0 top-0 z-30 flex h-14 items-center justify-between border-b border-slate-200 bg-white px-4 lg:hidden">
        <span class="truncate text-sm font-semibold">{{ auth()->user()->business->name ?? '—' }}</span>
        <button @click="sidebarOpen = true" class="rounded-md p-2 text-xl leading-none text-slate-600 hover:bg-slate-100" aria-label="Abrir menú">☰</button>
    </div>

    {{-- Fondo oscuro al abrir el menú en móvil; toca fuera para cerrar. --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-slate-900/30 lg:hidden"></div>

    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-60 shrink-0 -translate-x-full transform flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-out lg:static lg:translate-x-0">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-wide text-slate-400">Negocio</p>
                <p class="mt-0.5 truncate text-sm font-semibold">{{ auth()->user()->business->name ?? '—' }}</p>
            </div>
            <button @click="sidebarOpen = false" class="text-slate-400 hover:text-slate-600 lg:hidden" aria-label="Cerrar menú">✕</button>
        </div>
        <nav class="flex-1 overflow-y-auto p-3">
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
                ['pos.branches.index', 'Sucursales', 'pos.branches.*'],
                ['pos.employees.index', 'Empleados', 'pos.employees.*'],
                ['pos.cash-sessions.index', 'Cortes de caja', 'pos.cash-sessions.index'],
                ['pos.reports.sales', 'Reportes', 'pos.reports.*'],
                ['pos.settings.edit', 'Configuración', 'pos.settings.*'],
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
        <div class="flex items-center justify-between border-t border-slate-200 px-5 py-3">
            <a href="{{ route('pos.profile.edit') }}" class="text-xs text-slate-500 hover:text-slate-900 {{ request()->routeIs('pos.profile.edit') ? 'font-medium text-indigo-700' : '' }}">Mi perfil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-xs text-slate-500 hover:text-slate-900">Salir</button>
            </form>
        </div>
    </aside>
    <main class="min-w-0 flex-1 bg-slate-50 pt-14 lg:pt-0">
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
