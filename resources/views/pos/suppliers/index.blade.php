@extends('layouts.pos')
@section('title', 'Proveedores')

@section('content')
@php
    // Un solo @json() por atributo x-data: varios anidados truncan la
    // expresión (Blade) y dejan a Alpine sin poder inicializar el componente.
    $initConfig = [
        'open' => $errors->any(),
        'old' => [
            'name' => old('name', ''), 'contact_name' => old('contact_name', ''), 'phone' => old('phone', ''),
            'email' => old('email', ''), 'address' => old('address', ''), 'notes' => old('notes', ''),
        ],
    ];
@endphp
<div class="mx-auto max-w-5xl px-8 py-8"
     x-data="supplierForm({{ Illuminate\Support\Js::from($initConfig) }})">
    <header class="mb-6 flex items-end justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Catálogo</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Proveedores</h1>
        </div>
        <button @click="openCreate()" class="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            Nuevo proveedor
        </button>
    </header>

    <div class="mb-4">
        <a href="{{ $includeArchived ? route('pos.suppliers.index') : route('pos.suppliers.index', ['archived' => 1]) }}"
           class="whitespace-nowrap rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
            {{ $includeArchived ? 'Solo activos' : 'Ver archivados' }}
        </a>
    </div>

    @if ($suppliers->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Sin proveedores todavía</p>
            <p class="mt-1 text-sm text-slate-500">Agrega tu primer proveedor para registrar compras.</p>
        </div>
    @else
        <div class="rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-3 pl-5 pr-3 font-medium">Nombre</th>
                        <th class="px-3 py-3 font-medium">Contacto</th>
                        <th class="px-3 py-3 font-medium">Teléfono</th>
                        <th class="px-3 py-3 font-medium">Correo</th>
                        <th class="py-3 pl-3 pr-5 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($suppliers as $s)
                        @php
                            $editPayload = [
                                'id' => $s->id, 'name' => $s->name, 'contact_name' => $s->contact_name,
                                'phone' => $s->phone, 'email' => $s->email, 'address' => $s->address, 'notes' => $s->notes,
                            ];
                        @endphp
                        <tr class="border-b border-slate-100 last:border-0 {{ $s->is_active ? '' : 'bg-slate-50/60' }}">
                            <td class="py-3 pl-5 pr-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ $s->name }}</span>
                                    @unless ($s->is_active)
                                        <span class="rounded bg-slate-200 px-1.5 py-0.5 text-xs text-slate-500">Archivado</span>
                                    @endunless
                                </div>
                            </td>
                            <td class="px-3 py-3 text-slate-500">{{ $s->contact_name ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-500">{{ $s->phone ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-500">{{ $s->email ?? '—' }}</td>
                            <td class="py-3 pl-3 pr-5">
                                <div class="flex items-center justify-end gap-3">
                                    <button @click="openEdit({{ Illuminate\Support\Js::from($editPayload) }})"
                                            class="text-indigo-600 hover:text-indigo-500">Editar</button>
                                    <form method="POST" action="{{ route('pos.suppliers.active', $s->id) }}"
                                          onsubmit="return confirm('¿Seguro que quieres {{ $s->is_active ? 'archivar' : 'restaurar' }} &quot;{{ $s->name }}&quot;?');">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $s->is_active ? 0 : 1 }}">
                                        <button class="text-slate-500 hover:text-slate-900">{{ $s->is_active ? 'Archivar' : 'Restaurar' }}</button>
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
                <h2 class="text-sm font-semibold" x-text="mode === 'edit' ? 'Editar proveedor' : 'Nuevo proveedor'"></h2>
                <button @click="open = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form :action="action" method="POST" class="flex flex-1 flex-col">
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>
                <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Nombre</label>
                        <input name="name" x-model="form.name" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Persona de contacto</label>
                        <input name="contact_name" x-model="form.contact_name" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Teléfono</label>
                            <input name="phone" x-model="form.phone" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Correo</label>
                            <input name="email" x-model="form.email" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Dirección</label>
                        <textarea name="address" x-model="form.address" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></textarea>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Notas</label>
                        <textarea name="notes" x-model="form.notes" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></textarea>
                    </div>
                </div>
                <div class="flex gap-3 border-t border-slate-200 px-6 py-4">
                    <button type="button" @click="open = false" class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                            x-text="mode === 'edit' ? 'Guardar cambios' : 'Crear proveedor'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function supplierForm(config) {
        return {
            open: config.open,
            mode: 'create',
            editId: null,
            storeUrl: @json(route('pos.suppliers.store')),
            updateBase: @json(url('pos/suppliers')),
            form: { ...config.old },
            get action() { return this.mode === 'edit' ? `${this.updateBase}/${this.editId}` : this.storeUrl; },
            openCreate() { this.mode = 'create'; this.editId = null; this.form = { name: '', contact_name: '', phone: '', email: '', address: '', notes: '' }; this.open = true; },
            openEdit(s) {
                this.mode = 'edit'; this.editId = s.id;
                this.form = { name: s.name, contact_name: s.contact_name ?? '', phone: s.phone ?? '', email: s.email ?? '', address: s.address ?? '', notes: s.notes ?? '' };
                this.open = true;
            },
        };
    }
</script>
@endsection
