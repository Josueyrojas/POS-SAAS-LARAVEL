@extends('layouts.pos')
@section('title', 'Configuración')

@section('content')
<div class="mx-auto max-w-md px-8 py-8">
    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Administración</p>
    <h1 class="mt-1 text-2xl font-semibold tracking-tight">Configuración del negocio</h1>
    <p class="mt-1 text-sm text-slate-500">Estos datos aparecen en el recibo impreso de cada venta.</p>

    <form method="POST" action="{{ route('pos.settings.update') }}" class="mt-6 space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf @method('PATCH')
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Dirección</label>
            <input name="address" value="{{ old('address', $business->address) }}"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
            @error('address')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Teléfono</label>
            <input name="phone" value="{{ old('phone', $business->phone) }}"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
            @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">URL del logo (opcional)</label>
            <input name="logo_url" type="url" placeholder="https://…" value="{{ old('logo_url', $business->logo_url) }}"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
            @error('logo_url')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Pie del ticket (opcional)</label>
            <textarea name="receipt_footer" rows="2" placeholder="¡Gracias por su compra!"
                      class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">{{ old('receipt_footer', $business->receipt_footer) }}</textarea>
            @error('receipt_footer')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <button class="w-full rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            Guardar
        </button>
    </form>
</div>
@endsection
