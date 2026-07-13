@extends('layouts.pos')
@section('title', 'Nueva compra')

@section('content')
@php
    $initConfig = [
        'products' => $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'unit' => $p->unitOfMeasure->abbreviation]),
    ];
@endphp
<div class="mx-auto max-w-3xl px-8 py-8"
     x-data="purchaseForm({{ Illuminate\Support\Js::from($initConfig) }})">
    <header class="mb-6">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Compras</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Nueva compra</h1>
    </header>

    <form method="POST" action="{{ route('pos.purchases.store') }}" @submit="beforeSubmit">
        @csrf
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Proveedor</label>
                    <select name="supplier_id" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        <option value="">Selecciona…</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id') === $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Fecha</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date', now()->format('Y-m-d')) }}" required
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    @error('purchase_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">No. de factura (opcional)</label>
                    <input name="invoice_number" value="{{ old('invoice_number') }}"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>
                @if ($branches->count() > 1)
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Sucursal</label>
                        <select name="branch_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            <option value="">Sin especificar</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" @selected(old('branch_id') === $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif ($branches->count() === 1)
                    <input type="hidden" name="branch_id" value="{{ $branches->first()->id }}">
                @endif
            </div>
        </div>

        <div class="mt-4 rounded-lg border border-slate-200 bg-white p-6">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold">Productos</h2>
                <button type="button" @click="addRow()" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">+ Agregar línea</button>
            </div>
            @error('items')<p class="mb-2 text-xs text-rose-600">{{ $message }}</p>@enderror

            <template x-for="(row, i) in rows" :key="i">
                <div class="mb-2 grid grid-cols-12 gap-2">
                    <select :name="`items[${i}][product_id]`" x-model="row.product_id" required
                            class="col-span-6 rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:border-slate-500 focus:outline-none">
                        <option value="">Producto…</option>
                        <template x-for="p in products" :key="p.id">
                            <option :value="p.id" x-text="`${p.name} (${p.unit})`"></option>
                        </template>
                    </select>
                    <input :name="`items[${i}][quantity]`" x-model="row.quantity" type="number" step="0.001" min="0.001" placeholder="Cantidad"
                           class="col-span-2 rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:border-slate-500 focus:outline-none">
                    <input :name="`items[${i}][unit_cost]`" x-model="row.unit_cost" type="number" step="0.01" min="0" placeholder="Costo unit."
                           class="col-span-3 rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:border-slate-500 focus:outline-none">
                    <button type="button" @click="removeRow(i)" class="col-span-1 text-slate-400 hover:text-rose-600">✕</button>
                </div>
            </template>
        </div>

        <div class="mt-4">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Notas</label>
            <textarea name="notes" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">{{ old('notes') }}</textarea>
        </div>

        <div class="mt-6 flex gap-3">
            <a href="{{ route('pos.purchases.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</a>
            <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Registrar compra</button>
        </div>
    </form>
</div>

<script>
    function purchaseForm(config) {
        return {
            products: config.products,
            rows: [{ product_id: '', quantity: '', unit_cost: '' }],
            addRow() { this.rows.push({ product_id: '', quantity: '', unit_cost: '' }); },
            removeRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1); },
            beforeSubmit() {
                this.rows = this.rows.filter(r => r.product_id && r.quantity && r.unit_cost !== '');
                if (this.rows.length === 0) this.rows = [{ product_id: '', quantity: '', unit_cost: '' }];
            },
        };
    }
</script>
@endsection
