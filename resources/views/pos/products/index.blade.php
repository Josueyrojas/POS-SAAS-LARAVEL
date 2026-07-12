@extends('layouts.pos')
@section('title', 'Productos')

@section('content')
@php
    // OJO: no anidar múltiples @json() dentro de un mismo atributo x-data — el
    // compilador de Blade trunca la expresión al tercer/cuarto @json() y deja
    // el x-data con JS inválido (Alpine falla en silencio: el modal queda
    // visualmente "abierto" y ningún botón responde). Se precomputa un único
    // array PHP y se vuelca con Js::from(), que sí soporta esto sin límite.
    $initConfig = [
        'open' => $errors->any(),
        'old' => [
            'name' => old('name', ''),
            'sku' => old('sku', ''),
            'category_id' => old('category_id', ''),
            'unit_of_measure_id' => old('unit_of_measure_id', ''),
            'retail_price' => old('retail_price', ''),
            'wholesale_price' => old('wholesale_price', ''),
            'wholesale_min_qty' => old('wholesale_min_qty', ''),
            'cost_price' => old('cost_price', ''),
            'stock' => old('stock', '0'),
            'stock_minimo' => old('stock_minimo', '0'),
        ],
    ];
@endphp
<div class="mx-auto max-w-6xl px-8 py-8"
     x-data="productForm({{ Illuminate\Support\Js::from($initConfig) }})">
    <header class="mb-6 flex items-end justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Inventario</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Productos</h1>
        </div>
        @can('manage-products')
            <button @click="openCreate()" class="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                Nuevo producto
            </button>
        @endcan
    </header>

    <div class="mb-4 flex items-center gap-3">
        <input x-model="q" type="text" placeholder="Buscar por nombre o SKU…"
               class="flex-1 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
        <a href="{{ $includeArchived ? route('pos.products.index') : route('pos.products.index', ['archived' => 1]) }}"
           class="whitespace-nowrap rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
            {{ $includeArchived ? 'Solo activos' : 'Ver archivados' }}
        </a>
    </div>

    @if ($products->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Tu catálogo está vacío</p>
            <p class="mt-1 text-sm text-slate-500">Agrega tu primer producto para empezar a vender.</p>
            @can('manage-products')
                <button @click="openCreate()" class="mt-4 rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">Nuevo producto</button>
            @endcan
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-3 pl-5 pr-3 font-medium">Producto</th>
                        <th class="px-3 py-3 font-medium">Categoría</th>
                        <th class="px-3 py-3 font-medium">SKU</th>
                        <th class="px-3 py-3 text-right font-medium">Menudeo</th>
                        <th class="px-3 py-3 text-right font-medium">Mayoreo</th>
                        <th class="px-3 py-3 text-center font-medium">Stock</th>
                        <th class="py-3 pl-3 pr-5 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $p)
                        @php
                            $low = $p->isLowStock();
                            $dot = (float) $p->stock === 0.0 ? 'bg-rose-500' : ($low ? 'bg-amber-400' : 'bg-emerald-500');
                            $txt = (float) $p->stock === 0.0 ? 'text-rose-600' : ($low ? 'text-amber-600' : 'text-slate-600');
                            $editPayload = [
                                'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku,
                                'category_id' => $p->category_id, 'unit_of_measure_id' => $p->unit_of_measure_id,
                                'retail_price' => (string) $p->retail_price,
                                'wholesale_price' => $p->wholesale_price ? (string) $p->wholesale_price : '',
                                'wholesale_min_qty' => $p->wholesale_min_qty ? (string) $p->wholesale_min_qty : '',
                                'cost_price' => $p->cost_price ? (string) $p->cost_price : '',
                                'stock' => (string) $p->stock, 'stock_minimo' => (string) $p->stock_minimo,
                            ];
                        @endphp
                        <tr class="border-b border-slate-100 last:border-0 {{ $p->is_active ? '' : 'bg-slate-50/60' }}"
                            x-show="q === '' || {{ Illuminate\Support\Js::from(Str::lower($p->name.' '.$p->sku)) }}.includes(q.toLowerCase())">
                            <td class="py-3 pl-5 pr-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ $p->name }}</span>
                                    @unless ($p->is_active)
                                        <span class="rounded bg-slate-200 px-1.5 py-0.5 text-xs text-slate-500">Archivado</span>
                                    @endunless
                                </div>
                            </td>
                            <td class="px-3 py-3 text-slate-500">{{ $p->category->name ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-500">{{ $p->sku ?? '—' }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-700">${{ number_format($p->retail_price, 2) }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-500">
                                @if ($p->wholesale_price)
                                    ${{ number_format($p->wholesale_price, 2) }}
                                    <span class="text-xs text-slate-400">(≥{{ rtrim(rtrim(number_format($p->wholesale_min_qty, 3), '0'), '.') }})</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    @can('manage-products')
                                        <form method="POST" action="{{ route('pos.products.stock', $p->id) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="delta" value="-1">
                                            <button {{ (float) $p->stock === 0.0 ? 'disabled' : '' }}
                                                    class="flex h-6 w-6 items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-100 disabled:opacity-40">−</button>
                                        </form>
                                    @endcan
                                    <span class="flex min-w-[4.5rem] items-center justify-center gap-1.5 tabular-nums {{ $txt }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>{{ rtrim(rtrim(number_format($p->stock, 3), '0'), '.') }} {{ $p->unitOfMeasure->abbreviation ?? '' }}
                                    </span>
                                    @can('manage-products')
                                        <form method="POST" action="{{ route('pos.products.stock', $p->id) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="delta" value="1">
                                            <button class="flex h-6 w-6 items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-100">+</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                            <td class="py-3 pl-3 pr-5">
                                @can('manage-products')
                                    <div class="flex items-center justify-end gap-3">
                                        <button @click="openEdit({{ Illuminate\Support\Js::from($editPayload) }})"
                                                class="text-indigo-600 hover:text-indigo-500">Editar</button>
                                        <form method="POST" action="{{ route('pos.products.active', $p->id) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $p->is_active ? 0 : 1 }}">
                                            <button class="text-slate-500 hover:text-slate-900">{{ $p->is_active ? 'Archivar' : 'Restaurar' }}</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Modal crear/editar (reutilizado) --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex justify-end bg-slate-900/30">
        <div class="flex h-full w-full max-w-md flex-col bg-white shadow-xl" @click.outside="open = false">
            <div class="flex h-14 items-center justify-between border-b border-slate-200 px-6">
                <h2 class="text-sm font-semibold" x-text="mode === 'edit' ? 'Editar producto' : 'Nuevo producto'"></h2>
                <button @click="open = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form :action="action" method="POST" class="flex flex-1 flex-col">
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>
                <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Nombre</label>
                        <input name="name" x-model="form.name" placeholder="Café 250g"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">SKU (opcional)</label>
                            <input name="sku" x-model="form.sku" placeholder="CAF-250"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            @error('sku')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Categoría</label>
                            <select name="category_id" x-model="form.category_id"
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                                <option value="">Sin categoría</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Unidad de medida</label>
                        <select name="unit_of_measure_id" x-model="form.unit_of_measure_id" required
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            <option value="">Selecciona…</option>
                            @foreach ($units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->abbreviation }})</option>
                            @endforeach
                        </select>
                        @error('unit_of_measure_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Precio menudeo</label>
                            <input name="retail_price" x-model="form.retail_price" inputmode="decimal" placeholder="0.00"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            @error('retail_price')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Costo (opcional)</label>
                            <input name="cost_price" x-model="form.cost_price" inputmode="decimal" placeholder="0.00"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        </div>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-400">Precio de mayoreo (opcional)</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Precio mayoreo</label>
                                <input name="wholesale_price" x-model="form.wholesale_price" inputmode="decimal" placeholder="0.00"
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Cantidad mínima</label>
                                <input name="wholesale_min_qty" x-model="form.wholesale_min_qty" inputmode="decimal" placeholder="12"
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            </div>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500">Se aplica automático cuando la cantidad vendida alcanza el mínimo.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Stock</label>
                            <input name="stock" x-model="form.stock" inputmode="decimal" placeholder="0"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            @error('stock')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Stock mínimo (alerta)</label>
                            <input name="stock_minimo" x-model="form.stock_minimo" inputmode="decimal" placeholder="5"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 border-t border-slate-200 px-6 py-4">
                    <button type="button" @click="open = false" class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                            x-text="mode === 'edit' ? 'Guardar cambios' : 'Crear producto'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function productForm(config) {
        return {
            q: '',
            open: config.open,
            mode: 'create',
            editId: null,
            storeUrl: @json(route('pos.products.store')),
            updateBase: @json(url('pos/products')),
            form: { ...config.old },
            get action() {
                return this.mode === 'edit' ? `${this.updateBase}/${this.editId}` : this.storeUrl;
            },
            openCreate() {
                this.mode = 'create';
                this.editId = null;
                this.form = {
                    name: '', sku: '', category_id: '', unit_of_measure_id: '',
                    retail_price: '', wholesale_price: '', wholesale_min_qty: '', cost_price: '',
                    stock: '0', stock_minimo: '0',
                };
                this.open = true;
            },
            openEdit(p) {
                this.mode = 'edit';
                this.editId = p.id;
                this.form = {
                    name: p.name, sku: p.sku ?? '', category_id: p.category_id ?? '', unit_of_measure_id: p.unit_of_measure_id ?? '',
                    retail_price: p.retail_price, wholesale_price: p.wholesale_price ?? '', wholesale_min_qty: p.wholesale_min_qty ?? '',
                    cost_price: p.cost_price ?? '', stock: p.stock, stock_minimo: p.stock_minimo,
                };
                this.open = true;
            },
        };
    }
</script>
@endsection
