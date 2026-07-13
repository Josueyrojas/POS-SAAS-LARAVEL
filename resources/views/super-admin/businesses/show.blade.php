@extends('layouts.platform')
@section('title', $business->name)

@php use App\Enums\BusinessStatus; use App\Enums\BusinessPlan; @endphp

@section('content')
<div class="mx-auto max-w-4xl px-8 py-8" x-data="{ confirmDelete: false, nameInput: '' }">
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
            <form method="POST" action="{{ route('super-admin.businesses.tax-rate', $business->id) }}" class="flex items-center gap-1">
                @csrf @method('PATCH')
                <label class="text-xs text-slate-400">IVA</label>
                <input name="tax_rate" type="number" step="0.01" min="0" max="100" value="{{ $business->tax_rate }}"
                       class="w-16 rounded-md border border-slate-300 px-2 py-1 text-xs">
                <span class="text-xs text-slate-400">%</span>
                <button class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Guardar</button>
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
        <div class="rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-2.5 pl-5 pr-3 font-medium">Nombre</th>
                        <th class="px-3 py-2.5 font-medium">Correo</th>
                        <th class="px-3 py-2.5 font-medium">Estado</th>
                        <th class="px-3 py-2.5 font-medium">Último acceso</th>
                        <th class="py-2.5 pl-3 pr-5 text-right font-medium">Acciones</th>
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
                            <td class="py-2.5 pl-3 pr-5 text-right">
                                @if (is_null($u->last_login_at))
                                    <form method="POST" action="{{ route('super-admin.businesses.admins.resend-invite', [$business->id, $u->id]) }}">
                                        @csrf
                                        <button class="text-indigo-600 hover:text-indigo-500">Reenviar invitación</button>
                                    </form>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>
    </section>

    <section class="mt-10 rounded-lg border border-rose-200 bg-rose-50 p-5">
        <h2 class="text-sm font-semibold text-rose-800">Zona de peligro</h2>
        <p class="mt-1 text-sm text-rose-700">
            Eliminar este negocio borra permanentemente TODOS sus datos (ventas, productos, usuarios,
            compras, clientes, todo). No se puede deshacer.
        </p>
        <button @click="confirmDelete = true" class="mt-3 rounded-md border border-rose-300 bg-white px-3.5 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">
            Eliminar negocio permanentemente
        </button>
    </section>

    <div x-show="confirmDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl" @click.outside="confirmDelete = false">
            <h2 class="text-sm font-semibold text-rose-700">Eliminar "{{ $business->name }}"</h2>
            <p class="mt-1 text-xs text-slate-500">
                Esta acción es irreversible. Para confirmar, escribe el nombre exacto del negocio:
                <span class="font-medium text-slate-700">{{ $business->name }}</span>
            </p>
            <form method="POST" action="{{ route('super-admin.businesses.destroy', $business->id) }}" class="mt-4">
                @csrf @method('DELETE')
                <input name="confirm_name" x-model="nameInput" type="text" autocomplete="off"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none">
                @error('confirm_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                <div class="mt-4 flex gap-3">
                    <button type="button" @click="confirmDelete = false" class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button :disabled="nameInput !== {{ Illuminate\Support\Js::from($business->name) }}"
                            class="flex-1 rounded-md bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-500 disabled:opacity-40">
                        Eliminar definitivamente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
