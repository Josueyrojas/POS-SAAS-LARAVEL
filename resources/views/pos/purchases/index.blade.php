@extends('layouts.pos')
@section('title', 'Compras')

@section('content')
<div class="mx-auto max-w-5xl px-8 py-8">
    <header class="mb-6 flex items-end justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Proveedores</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Compras</h1>
        </div>
        <a href="{{ route('pos.purchases.create') }}" class="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            Nueva compra
        </a>
    </header>

    @if ($purchases->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Sin compras registradas</p>
            <p class="mt-1 text-sm text-slate-500">Registra una compra para que el stock entre ligado a un proveedor y a un costo.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-3 pl-5 pr-3 font-medium">Proveedor</th>
                        <th class="px-3 py-3 font-medium">Fecha</th>
                        <th class="px-3 py-3 font-medium">Factura</th>
                        <th class="px-3 py-3 font-medium">Estado</th>
                        <th class="px-3 py-3 text-right font-medium">Total</th>
                        <th class="py-3 pl-3 pr-5 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchases as $p)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="py-3 pl-5 pr-3 font-medium">{{ $p->supplier->name }}</td>
                            <td class="px-3 py-3 text-slate-500">{{ $p->purchase_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-3 text-slate-500">{{ $p->invoice_number ?? '—' }}</td>
                            <td class="px-3 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $p->status->badge() }}">{{ $p->status->label() }}</span>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-700">${{ number_format($p->total, 2) }}</td>
                            <td class="py-3 pl-3 pr-5">
                                @if ($p->status === \App\Enums\PurchaseStatus::PENDING)
                                    <div class="flex items-center justify-end gap-3">
                                        <form method="POST" action="{{ route('pos.purchases.receive', $p->id) }}"
                                              onsubmit="return confirm('¿Confirmar recepción? El stock de los productos subirá.');">
                                            @csrf @method('PATCH')
                                            <button class="text-indigo-600 hover:text-indigo-500">Recibir</button>
                                        </form>
                                        <form method="POST" action="{{ route('pos.purchases.cancel', $p->id) }}"
                                              onsubmit="return confirm('¿Cancelar esta compra?');">
                                            @csrf @method('PATCH')
                                            <button class="text-slate-500 hover:text-rose-600">Cancelar</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
