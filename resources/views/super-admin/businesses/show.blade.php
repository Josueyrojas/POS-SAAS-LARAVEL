@extends('layouts.platform')
@section('title', $business->name)

@php use App\Enums\BusinessStatus; use App\Enums\BusinessPlan; @endphp

@section('content')
<div class="mx-auto max-w-4xl px-8 py-8">
    <a href="{{ route('super-admin.businesses.index') }}" class="text-sm text-slate-500 hover:text-slate-900">← Negocios</a>

    <header class="mt-4 flex items-start justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight">{{ $business->name }}</h1>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $business->status->badge() }}">{{ $business->status->label() }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-400">/{{ $business->slug }}</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('super-admin.businesses.status', $business->id) }}">
                @csrf @method('PATCH')
                <select name="status" onchange="this.form.submit()" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs">
                    @foreach (BusinessStatus::cases() as $st)
                        <option value="{{ $st->value }}" @selected($business->status === $st)>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </form>
            <form method="POST" action="{{ route('super-admin.businesses.plan', $business->id) }}">
                @csrf @method('PATCH')
                <select name="plan" onchange="this.form.submit()" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs">
                    @foreach (BusinessPlan::cases() as $plan)
                        <option value="{{ $plan->value }}" @selected($business->plan === $plan)>{{ $plan->label() }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </header>

    <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([['Usuarios', $business->users_count], ['Sucursales', $business->branches_count], ['Productos', $business->products_count], ['Ventas', $business->sales_count]] as [$label, $val])
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $label }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">{{ $val }}</p>
            </div>
        @endforeach
    </div>

    <section class="mt-8">
        <h2 class="mb-3 text-sm font-medium uppercase tracking-wide text-slate-400">Administradores del negocio</h2>
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-2.5 pl-5 pr-3 font-medium">Nombre</th>
                        <th class="px-3 py-2.5 font-medium">Correo</th>
                        <th class="px-3 py-2.5 font-medium">Estado</th>
                        <th class="px-3 py-2.5 font-medium">Último acceso</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($admins as $u)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="py-2.5 pl-5 pr-3">{{ $u->name ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-slate-600">{{ $u->email }}</td>
                            <td class="px-3 py-2.5">
                                @if ($u->is_active)
                                    <span class="text-emerald-600">Activo</span>
                                @else
                                    <span class="text-slate-400">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-500">{{ $u->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
