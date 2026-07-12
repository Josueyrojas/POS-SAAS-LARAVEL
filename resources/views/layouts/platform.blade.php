<!DOCTYPE html>
<html lang="es">
<head>@include('partials.head')</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<div class="flex min-h-screen">
    <aside class="flex w-60 shrink-0 flex-col border-r border-slate-200 bg-white">
        <div class="flex h-14 items-center gap-2 border-b border-slate-200 px-5">
            <span class="h-2 w-2 rounded-full bg-slate-900"></span>
            <span class="text-sm font-semibold tracking-tight">Plataforma</span>
        </div>
        <nav class="flex-1 p-3">
            <a href="{{ route('super-admin.businesses.index') }}"
               class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('super-admin.businesses.*') ? 'bg-slate-100 font-medium text-slate-900' : 'text-slate-600 hover:bg-slate-100' }}">
                Negocios
            </a>
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
            <div class="mx-auto max-w-6xl px-8 pt-4">
                <div class="rounded-md bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</div>
            </div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
