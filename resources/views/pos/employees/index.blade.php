@extends('layouts.pos')
@section('title', 'Empleados')

@section('content')
@php
    // Un solo @json() por atributo x-data: varios anidados truncan la
    // expresión (Blade) y dejan a Alpine sin poder inicializar el componente.
    $initConfig = [
        'open' => $errors->any(),
        'old' => ['name' => old('name', ''), 'email' => old('email', '')],
    ];
@endphp
<div class="mx-auto max-w-3xl px-8 py-8"
     x-data="employeeForm({{ Illuminate\Support\Js::from($initConfig) }})">
    <header class="mb-6 flex items-end justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Equipo</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Empleados</h1>
        </div>
        <button @click="openCreate()" class="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            Nuevo empleado
        </button>
    </header>

    @if ($employees->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Sin empleados todavía</p>
            <p class="mt-1 text-sm text-slate-500">Da de alta a tu primer cajero para que pueda usar el punto de venta.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-3 pl-5 pr-3 font-medium">Nombre</th>
                        <th class="px-3 py-3 font-medium">Correo</th>
                        <th class="px-3 py-3 font-medium">Último acceso</th>
                        <th class="py-3 pl-3 pr-5 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $e)
                        @php($editPayload = ['id' => $e->id, 'name' => $e->name, 'email' => $e->email])
                        <tr class="border-b border-slate-100 last:border-0 {{ $e->is_active ? '' : 'bg-slate-50/60' }}">
                            <td class="py-3 pl-5 pr-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ $e->name }}</span>
                                    @unless ($e->is_active)
                                        <span class="rounded bg-slate-200 px-1.5 py-0.5 text-xs text-slate-500">Deshabilitado</span>
                                    @endunless
                                </div>
                            </td>
                            <td class="px-3 py-3 text-slate-500">{{ $e->email }}</td>
                            <td class="px-3 py-3 text-slate-500">{{ $e->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                            <td class="py-3 pl-3 pr-5">
                                <div class="flex items-center justify-end gap-3">
                                    <button @click="openEdit({{ Illuminate\Support\Js::from($editPayload) }})"
                                            class="text-indigo-600 hover:text-indigo-500">Editar</button>
                                    <form method="POST" action="{{ route('pos.employees.active', $e->id) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $e->is_active ? 0 : 1 }}">
                                        <button class="text-slate-500 hover:text-slate-900">{{ $e->is_active ? 'Deshabilitar' : 'Habilitar' }}</button>
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
                <h2 class="text-sm font-semibold" x-text="mode === 'edit' ? 'Editar empleado' : 'Nuevo empleado'"></h2>
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
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Correo</label>
                        <input name="email" x-model="form.email" type="email" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            <span x-show="mode === 'create'">Contraseña</span>
                            <span x-show="mode === 'edit'">Nueva contraseña (opcional)</span>
                        </label>
                        <input name="password" x-model="form.password" type="password" placeholder="Mínimo 8 caracteres"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex gap-3 border-t border-slate-200 px-6 py-4">
                    <button type="button" @click="open = false" class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                            x-text="mode === 'edit' ? 'Guardar cambios' : 'Crear empleado'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function employeeForm(config) {
        return {
            open: config.open,
            mode: 'create',
            editId: null,
            storeUrl: @json(route('pos.employees.store')),
            updateBase: @json(url('pos/employees')),
            form: { name: config.old.name, email: config.old.email, password: '' },
            get action() { return this.mode === 'edit' ? `${this.updateBase}/${this.editId}` : this.storeUrl; },
            openCreate() { this.mode = 'create'; this.editId = null; this.form = { name: '', email: '', password: '' }; this.open = true; },
            openEdit(e) { this.mode = 'edit'; this.editId = e.id; this.form = { name: e.name, email: e.email, password: '' }; this.open = true; },
        };
    }
</script>
@endsection
