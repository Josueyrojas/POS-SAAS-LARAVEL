@extends('layouts.pos')
@section('title', 'Vender')

@section('content')
@php
    // Un solo @json() por atributo x-data: varios anidados truncan la
    // expresión (Blade) y dejan a Alpine sin poder inicializar el componente.
    $initConfig = [
        'searchUrl' => route('pos.sales.products-search'),
        'customers' => $customers->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
    ];
@endphp
<div class="mx-auto flex max-w-6xl flex-col gap-6 px-8 py-8 lg:flex-row"
     x-data="saleForm({{ Illuminate\Support\Js::from($initConfig) }})">
    {{-- Panel izquierdo: búsqueda de productos --}}
    <div class="flex-1">
        <header class="mb-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Punto de venta</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Nueva venta</h1>
        </header>

        <input x-model="q" @input.debounce.300ms="search()" type="text" placeholder="Buscar producto por nombre o SKU…"
               class="mb-4 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <template x-for="p in results" :key="p.id">
                <button type="button" @click="addToCart(p)"
                        class="rounded-lg border border-slate-200 bg-white p-3 text-left hover:border-indigo-400 hover:shadow-sm">
                    <p class="text-sm font-medium" x-text="p.name"></p>
                    <p class="mt-0.5 text-xs text-slate-400" x-text="(p.sku ?? '—') + ' · ' + p.stock + ' ' + p.unit + ' disp.'"></p>
                    <p class="mt-1.5 text-sm font-semibold text-indigo-600" x-text="'$' + parseFloat(p.retail_price).toFixed(2)"></p>
                </button>
            </template>
            <p x-show="q !== '' && results.length === 0" class="col-span-full text-sm text-slate-400">Sin resultados.</p>
        </div>
    </div>

    {{-- Panel derecho: carrito --}}
    <div class="w-full lg:w-96 lg:shrink-0">
        <form method="POST" action="{{ route('pos.sales.store') }}" @submit="beforeSubmit">
            @csrf
            <div class="rounded-lg border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h2 class="text-sm font-semibold">Carrito</h2>
                </div>

                <div class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
                    <template x-if="cart.length === 0">
                        <p class="px-4 py-8 text-center text-sm text-slate-400">Agrega productos para empezar.</p>
                    </template>
                    <template x-for="(item, i) in cart" :key="item.product_id">
                        <div class="px-4 py-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium" x-text="item.name"></p>
                                    <p class="text-xs" :class="priceFor(item).type === 'Mayoreo' ? 'text-emerald-600' : 'text-slate-400'"
                                       x-text="priceFor(item).type + ': $' + priceFor(item).price.toFixed(2) + ' / ' + item.unit"></p>
                                </div>
                                <button type="button" @click="cart.splice(i, 1)" class="text-slate-400 hover:text-rose-600">✕</button>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between gap-3">
                                <input type="number" x-model.number="item.quantity" :step="item.allows_decimal ? 0.001 : 1" min="0.001" :max="item.stock"
                                       class="w-24 rounded-md border border-slate-300 px-2 py-1 text-sm focus:border-indigo-500 focus:outline-none">
                                <span class="text-sm font-medium tabular-nums" x-text="'$' + (priceFor(item).price * item.quantity).toFixed(2)"></span>
                            </div>
                            <input type="hidden" :name="`items[${i}][product_id]`" :value="item.product_id">
                            <input type="hidden" :name="`items[${i}][quantity]`" :value="item.quantity">
                        </div>
                    </template>
                </div>

                <div class="space-y-3 border-t border-slate-200 px-4 py-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Cliente <span x-show="paymentMethod === 'CREDIT'">(obligatorio para fiado)</span></label>
                        <select name="customer_id" x-model="customerId" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:border-slate-500 focus:outline-none">
                            <option value="">Cliente mostrador</option>
                            <template x-for="c in customers" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                        <p x-show="paymentMethod === 'CREDIT' && customerId === ''" class="mt-1 text-xs text-rose-600">Elige un cliente para vender a crédito.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">Pago</label>
                            <select name="payment_method" x-model="paymentMethod" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:border-slate-500 focus:outline-none">
                                <option value="CASH">Efectivo</option>
                                <option value="CARD">Tarjeta</option>
                                <option value="TRANSFER">Transferencia</option>
                                <option value="CREDIT">Fiado (crédito)</option>
                                <option value="OTHER">Otro</option>
                            </select>
                        </div>
                        <div x-show="paymentMethod === 'CASH'">
                            <label class="mb-1 block text-xs font-medium text-slate-500">Recibido</label>
                            <input name="amount_tendered" x-model.number="amountTendered" type="number" step="0.01" min="0"
                                   class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:border-slate-500 focus:outline-none">
                        </div>
                    </div>
                    <div class="border-t border-slate-100 pt-3">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Descuento (opcional)</label>
                        <div class="flex gap-2">
                            <select name="discount_type" x-model="discountType" class="rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:border-slate-500 focus:outline-none">
                                <option value="">Sin descuento</option>
                                <option value="PERCENT">%</option>
                                <option value="FIXED">$ fijo</option>
                            </select>
                            <input name="discount_value" x-show="discountType !== ''" x-model.number="discountValue" type="number" step="0.01" min="0"
                                   class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:border-slate-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm text-slate-500">
                        <span>Subtotal</span>
                        <span class="tabular-nums" x-text="'$' + subtotal().toFixed(2)"></span>
                    </div>
                    <div x-show="discountAmount() > 0" class="flex items-center justify-between text-sm text-slate-500">
                        <span>Descuento</span>
                        <span class="tabular-nums" x-text="'-$' + discountAmount().toFixed(2)"></span>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                        <span class="text-sm font-medium text-slate-600">Total</span>
                        <span class="text-lg font-semibold tabular-nums" x-text="'$' + total().toFixed(2)"></span>
                    </div>
                    <p class="text-xs text-slate-400">IVA incluido</p>
                    <div x-show="paymentMethod === 'CASH' && amountTendered > 0" class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">Cambio</span>
                        <span class="font-medium tabular-nums" x-text="'$' + Math.max(0, amountTendered - total()).toFixed(2)"></span>
                    </div>
                </div>

                <div class="border-t border-slate-200 px-4 py-4">
                    <button :disabled="cart.length === 0 || submitting" type="submit"
                            class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-40">
                        <span x-show="!submitting">Cobrar</span>
                        <span x-show="submitting">Procesando…</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function saleForm(config) {
        return {
            searchUrl: config.searchUrl,
            customers: config.customers,
            q: '',
            results: [],
            cart: [],
            paymentMethod: 'CASH',
            amountTendered: null,
            customerId: '',
            discountType: '',
            discountValue: 0,
            submitting: false,
            async search() {
                if (this.q === '') { this.results = []; return; }
                const res = await fetch(`${this.searchUrl}?q=${encodeURIComponent(this.q)}`);
                this.results = await res.json();
            },
            addToCart(p) {
                const existing = this.cart.find(i => i.product_id === p.id);
                if (existing) { existing.quantity += 1; return; }
                this.cart.push({
                    product_id: p.id, name: p.name, unit: p.unit, allows_decimal: p.allows_decimal,
                    retail_price: p.retail_price, wholesale_price: p.wholesale_price, wholesale_min_qty: p.wholesale_min_qty,
                    stock: p.stock, quantity: 1,
                });
            },
            priceFor(item) {
                if (item.wholesale_price && item.wholesale_min_qty && item.quantity >= parseFloat(item.wholesale_min_qty)) {
                    return { price: parseFloat(item.wholesale_price), type: 'Mayoreo' };
                }
                return { price: parseFloat(item.retail_price), type: 'Menudeo' };
            },
            subtotal() {
                return this.cart.reduce((sum, item) => sum + this.priceFor(item).price * item.quantity, 0);
            },
            discountAmount() {
                const sub = this.subtotal();
                if (this.discountType === 'PERCENT') return Math.min(sub, sub * (this.discountValue || 0) / 100);
                if (this.discountType === 'FIXED') return Math.min(sub, this.discountValue || 0);
                return 0;
            },
            total() {
                return this.subtotal() - this.discountAmount();
            },
            beforeSubmit(e) {
                // Evita doble venta por doble clic/reintento: una vez que un
                // envío válido arranca, se deshabilita el botón hasta que la
                // página navegue (éxito) o el usuario la recargue (error).
                if (this.submitting) { e.preventDefault(); return; }
                if (this.cart.length === 0) { e.preventDefault(); return; }
                if (this.paymentMethod === 'CREDIT' && this.customerId === '') { e.preventDefault(); return; }
                this.cart = this.cart.filter(i => i.quantity > 0);
                this.submitting = true;
            },
        };
    }
</script>
@endsection
