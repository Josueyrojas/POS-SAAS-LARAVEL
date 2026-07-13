@extends('layouts.pos')
@section('title', 'Sucursales')

@section('content')
@php
    $initConfig = [
        'open' => $errors->any(),
        'old' => ['name' => old('name', ''), 'address' => old('address', '')],
    ];
@endphp
<div class="mx-auto max-w-3xl px-8 py-8"
     x-data="branchForm({{ Illuminate\Support\Js::from($initConfig) }})">
    <header class="mb-6 flex items-end justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Administración</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Sucursales</h1>
        </div>
        <button @click="openCreate()" class="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            Nueva sucursal
        </button>
    </header>

    <p class="mb-4 text-sm text-slate-500">
        Con 2 o más sucursales activas, se elige una al abrir el turno de caja y las ventas/compras quedan
        atribuidas a ella. El inventario sigue siendo uno solo, compartido entre todas.
    </p>

    <div class="mb-4">
        <a href="{{ $includeArchived ? route('pos.branches.index') : route('pos.branches.index', ['archived' => 1]) }}"
           class="whitespace-nowrap rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
            {{ $includeArchived ? 'Solo activas' : 'Ver archivadas' }}
        </a>
    </div>

    @if ($branches->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Sin sucursales todavía</p>
            <p class="mt-1 text-sm text-slate-500">Este negocio opera con una sola ubicación por ahora.</p>
        </div>
    @else
        <div class="rounded-lg border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="py-3 pl-5 pr-3 font-medium">Nombre</th>
                            <th class="px-3 py-3 font-medium">Dirección</th>
                            <th class="py-3 pl-3 pr-5 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branches as $b)
                            @php($editPayload = ['id' => $b->id, 'name' => $b->name, 'address' => $b->address])
                            <tr class="border-b border-slate-100 last:border-0 {{ $b->is_active ? '' : 'bg-slate-50/60' }}">
                                <td class="py-3 pl-5 pr-3">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ $b->name }}</span>
                                        @unless ($b->is_active)
                                            <span class="rounded bg-slate-200 px-1.5 py-0.5 text-xs text-slate-500">Archivada</span>
                                        @endunless
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-slate-500">{{ $b->address ?? '—' }}</td>
                                <td class="py-3 pl-3 pr-5">
                                    <div class="flex items-center justify-end gap-3">
                                        <button @click="openEdit({{ Illuminate\Support\Js::from($editPayload) }})"
                                                class="text-indigo-600 hover:text-indigo-500">Editar</button>
                                        <form method="POST" action="{{ route('pos.branches.active', $b->id) }}"
                                              onsubmit="return confirm('¿Seguro que quieres {{ $b->is_active ? 'archivar' : 'restaurar' }} &quot;{{ $b->name }}&quot;?');">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $b->is_active ? 0 : 1 }}">
                                            <button class="text-slate-500 hover:text-slate-900">{{ $b->is_active ? 'Archivar' : 'Restaurar' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex justify-end bg-slate-900/30">
        <div class="flex h-full w-full max-w-md flex-col bg-white shadow-xl" @click.outside="open = false">
            <div class="flex h-14 items-center justify-between border-b border-slate-200 px-6">
                <h2 class="text-sm font-semibold" x-text="mode === 'edit' ? 'Editar sucursal' : 'Nueva sucursal'"></h2>
                <button @click="open = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form :action="action" method="POST" class="flex flex-1 flex-col">
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>
                <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Nombre</label>
                        <input name="name" x-model="form.name" placeholder="Sucursal Centro"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Dirección (opcional)</label>
                        <textarea name="address" x-model="form.address" rows="3"
                                  class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></textarea>
                        @error('address')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex gap-3 border-t border-slate-200 px-6 py-4">
                    <button type="button" @click="open = false" class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                            x-text="mode === 'edit' ? 'Guardar cambios' : 'Crear sucursal'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function branchForm(config) {
        return {
            open: config.open,
            mode: 'create',
            editId: null,
            storeUrl: @json(route('pos.branches.store')),
            updateBase: @json(url('pos/branches')),
            form: { name: config.old.name, address: config.old.address },
            get action() { return this.mode === 'edit' ? `${this.updateBase}/${this.editId}` : this.storeUrl; },
            openCreate() { this.mode = 'create'; this.editId = null; this.form = { name: '', address: '' }; this.open = true; },
            openEdit(b) { this.mode = 'edit'; this.editId = b.id; this.form = { name: b.name, address: b.address ?? '' }; this.open = true; },
        };
    }
</script>
@endsection
