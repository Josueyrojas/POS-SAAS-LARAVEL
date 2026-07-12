@extends('layouts.pos')
@section('title', 'Clientes')

@section('content')
@php
    // Un solo @json() por atributo x-data: varios anidados truncan la
    // expresión (Blade) y dejan a Alpine sin poder inicializar el componente.
    $initConfig = [
        'open' => $errors->any(),
        'old' => [
            'name' => old('name', ''), 'phone' => old('phone', ''), 'email' => old('email', ''),
            'document_id' => old('document_id', ''), 'address' => old('address', ''), 'notes' => old('notes', ''),
        ],
    ];
@endphp
<div class="mx-auto max-w-5xl px-8 py-8"
     x-data="customerForm({{ Illuminate\Support\Js::from($initConfig) }})">
    <header class="mb-6 flex items-end justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Clientes</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Clientes</h1>
        </div>
        <button @click="openCreate()" class="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            Nuevo cliente
        </button>
    </header>

    <div class="mb-4 flex items-center gap-3">
        <input x-model="q" type="text" placeholder="Buscar por nombre o teléfono…"
               class="flex-1 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
        <a href="{{ $includeArchived ? route('pos.customers.index') : route('pos.customers.index', ['archived' => 1]) }}"
           class="whitespace-nowrap rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
            {{ $includeArchived ? 'Solo activos' : 'Ver archivados' }}
        </a>
    </div>

    @if ($customers->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Sin clientes todavía</p>
            <p class="mt-1 text-sm text-slate-500">Agrega tu primer cliente para empezar.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-3 pl-5 pr-3 font-medium">Nombre</th>
                        <th class="px-3 py-3 font-medium">Teléfono</th>
                        <th class="px-3 py-3 font-medium">Correo</th>
                        <th class="px-3 py-3 font-medium">Documento</th>
                        <th class="py-3 pl-3 pr-5 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $c)
                        @php
                            $editPayload = [
                                'id' => $c->id, 'name' => $c->name, 'phone' => $c->phone, 'email' => $c->email,
                                'document_id' => $c->document_id, 'address' => $c->address, 'notes' => $c->notes,
                            ];
                        @endphp
                        <tr class="border-b border-slate-100 last:border-0 {{ $c->is_active ? '' : 'bg-slate-50/60' }}"
                            x-show="q === '' || {{ Illuminate\Support\Js::from(Str::lower($c->name.' '.$c->phone)) }}.includes(q.toLowerCase())">
                            <td class="py-3 pl-5 pr-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ $c->name }}</span>
                                    @unless ($c->is_active)
                                        <span class="rounded bg-slate-200 px-1.5 py-0.5 text-xs text-slate-500">Archivado</span>
                                    @endunless
                                </div>
                            </td>
                            <td class="px-3 py-3 text-slate-500">{{ $c->phone ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-500">{{ $c->email ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-500">{{ $c->document_id ?? '—' }}</td>
                            <td class="py-3 pl-3 pr-5">
                                <div class="flex items-center justify-end gap-3">
                                    <button @click="openEdit({{ Illuminate\Support\Js::from($editPayload) }})"
                                            class="text-indigo-600 hover:text-indigo-500">Editar</button>
                                    <form method="POST" action="{{ route('pos.customers.active', $c->id) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $c->is_active ? 0 : 1 }}">
                                        <button class="text-slate-500 hover:text-slate-900">{{ $c->is_active ? 'Archivar' : 'Restaurar' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex justify-end bg-slate-900/30">
        <div class="flex h-full w-full max-w-md flex-col bg-white shadow-xl" @click.outside="open = false">
            <div class="flex h-14 items-center justify-between border-b border-slate-200 px-6">
                <h2 class="text-sm font-semibold" x-text="mode === 'edit' ? 'Editar cliente' : 'Nuevo cliente'"></h2>
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
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Teléfono</label>
                            <input name="phone" x-model="form.phone" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Correo</label>
                            <input name="email" x-model="form.email" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Documento (RFC/NIT, opcional)</label>
                        <input name="document_id" x-model="form.document_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('document_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
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
                            x-text="mode === 'edit' ? 'Guardar cambios' : 'Crear cliente'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function customerForm(config) {
        return {
            q: '',
            open: config.open,
            mode: 'create',
            editId: null,
            storeUrl: @json(route('pos.customers.store')),
            updateBase: @json(url('pos/customers')),
            form: { ...config.old },
            get action() { return this.mode === 'edit' ? `${this.updateBase}/${this.editId}` : this.storeUrl; },
            openCreate() { this.mode = 'create'; this.editId = null; this.form = { name: '', phone: '', email: '', document_id: '', address: '', notes: '' }; this.open = true; },
            openEdit(c) {
                this.mode = 'edit'; this.editId = c.id;
                this.form = { name: c.name, phone: c.phone ?? '', email: c.email ?? '', document_id: c.document_id ?? '', address: c.address ?? '', notes: c.notes ?? '' };
                this.open = true;
            },
        };
    }
</script>
@endsection
