@extends('layouts.pos')
@section('title', 'Importar productos')

@section('content')
<div class="mx-auto max-w-3xl px-8 py-8">
    <a href="{{ route('pos.products.index') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Productos</a>

    <header class="mt-3 mb-6">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Inventario</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Importar productos</h1>
        <p class="mt-1 text-sm text-slate-500">
            Sube un CSV para dar de alta varios productos a la vez — útil si estás migrando desde otra plataforma.
        </p>
    </header>

    <div class="rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="text-sm font-semibold">Formato esperado</h2>
        <p class="mt-1 text-sm text-slate-500">
            Columnas: <code class="rounded bg-slate-100 px-1">sku, nombre, descripcion, categoria, unidad_medida,
            precio_menudeo, precio_mayoreo, cantidad_minima_mayoreo, precio_costo, stock, stock_minimo, activo</code>.
            Solo <strong>nombre</strong>, <strong>precio_menudeo</strong> y <strong>unidad_medida</strong> son obligatorias
            (unidad debe ser una existente: Pieza, Metro, Kilogramo, Litro, Paquete, Caja, Rollo o Par). Si el SKU
            coincide con un producto ya existente, se actualiza en vez de duplicarse.
        </p>
        <a href="{{ route('pos.products.export') }}" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500">
            Descargar tu catálogo actual como plantilla &rarr;
        </a>
    </div>

    <form method="POST" action="{{ route('pos.products.import') }}" enctype="multipart/form-data" class="mt-6 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Archivo CSV</label>
        <input type="file" name="file" accept=".csv,text/csv" required
               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-slate-200">
        @error('file')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        <button class="mt-4 rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            Importar
        </button>
    </form>

    @if (session('importResults'))
        @php
            $results = session('importResults');
            $created = collect($results)->where('status', 'creado')->count();
            $updated = collect($results)->where('status', 'actualizado')->count();
            $errors = collect($results)->where('status', 'error');
        @endphp
        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-semibold">Resultado de la importación</h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ $created }} creado(s), {{ $updated }} actualizado(s), {{ $errors->count() }} con error.
            </p>

            @if ($errors->isNotEmpty())
                <div class="mt-3 overflow-x-auto rounded-md border border-rose-200">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-rose-200 bg-rose-50 text-left text-xs uppercase tracking-wide text-rose-700">
                                <th class="py-2 pl-3 pr-2 font-medium">Fila</th>
                                <th class="px-2 py-2 font-medium">Producto</th>
                                <th class="py-2 pl-2 pr-3 font-medium">Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($errors as $row)
                                <tr class="border-b border-rose-100 last:border-0">
                                    <td class="py-2 pl-3 pr-2 text-slate-500">{{ $row['row'] }}</td>
                                    <td class="px-2 py-2">{{ $row['name'] }}</td>
                                    <td class="py-2 pl-2 pr-3 text-rose-700">{{ $row['message'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
