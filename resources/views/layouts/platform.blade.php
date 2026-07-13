<!DOCTYPE html>
<html lang="es">
<head>@include('partials.head')</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased" x-data="{ sidebarOpen: false }">
<div class="flex min-h-screen">
    {{-- Barra superior: solo visible en móvil (&lt; lg), da acceso al menú. --}}
    <div class="fixed inset-x-0 top-0 z-30 flex h-14 items-center justify-between border-b border-slate-200 bg-white px-4 lg:hidden">
        <span class="text-sm font-semibold">Plataforma</span>
        <button @click="sidebarOpen = true" class="rounded-md p-2 text-xl leading-none text-slate-600 hover:bg-slate-100" aria-label="Abrir menú">☰</button>
    </div>

    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-slate-900/30 lg:hidden"></div>

    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-60 shrink-0 -translate-x-full transform flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-out lg:static lg:translate-x-0">
        <div class="flex h-14 items-center justify-between gap-2 border-b border-slate-200 px-5">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-slate-900"></span>
                <span class="text-sm font-semibold tracking-tight">Plataforma</span>
            </div>
            <button @click="sidebarOpen = false" class="text-slate-400 hover:text-slate-600 lg:hidden" aria-label="Cerrar menú">✕</button>
        </div>
        <nav class="flex-1 p-3">
            <a href="{{ route('super-admin.businesses.index') }}"
               class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('super-admin.businesses.*') ? 'bg-slate-100 font-medium text-slate-900' : 'text-slate-600 hover:bg-slate-100' }}">
                Negocios
            </a>
        </nav>
        <div class="flex items-center justify-between border-t border-slate-200 px-5 py-3">
            <a href="{{ route('super-admin.profile.edit') }}" class="text-xs text-slate-500 hover:text-slate-900 {{ request()->routeIs('super-admin.profile.edit') ? 'font-medium text-slate-900' : '' }}">Mi perfil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-xs text-slate-500 hover:text-slate-900">Salir</button>
            </form>
        </div>
    </aside>
    <main class="min-w-0 flex-1 bg-slate-50 pt-14 lg:pt-0">
        @if (session('status'))
            <div class="mx-auto max-w-6xl px-8 pt-4">
                <div class="rounded-md bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</div>
            </div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
