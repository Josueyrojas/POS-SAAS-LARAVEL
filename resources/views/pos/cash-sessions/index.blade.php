@extends('layouts.pos')
@section('title', 'Cortes de caja')

@section('content')
<div class="mx-auto max-w-5xl px-8 py-8">
    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Administración</p>
    <h1 class="mt-1 text-2xl font-semibold tracking-tight">Cortes de caja</h1>

    @if ($sessions->isEmpty())
        <div class="mt-6 rounded-lg border border-dashed border-slate-300 bg-white py-16 text-center">
            <p class="text-sm font-medium">Sin turnos registrados todavía</p>
        </div>
    @else
        <div class="mt-6 rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-3 pl-5 pr-3 font-medium">Cajero</th>
                        <th class="px-3 py-3 font-medium">Abierto</th>
                        <th class="px-3 py-3 font-medium">Estado</th>
                        <th class="px-3 py-3 text-right font-medium">Fondo inicial</th>
                        <th class="px-3 py-3 text-right font-medium">Esperado</th>
                        <th class="px-3 py-3 text-right font-medium">Contado</th>
                        <th class="py-3 pl-3 pr-5 text-right font-medium">Diferencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $s)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="py-2.5 pl-5 pr-3 font-medium">{{ $s->user->name ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-slate-500">{{ $s->opening_at->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2.5">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $s->status->badge() }}">{{ $s->status->label() }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-500">${{ number_format($s->opening_amount, 2) }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-500">{{ $s->expected_amount !== null ? '$'.number_format($s->expected_amount, 2) : '—' }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-500">{{ $s->closing_amount !== null ? '$'.number_format($s->closing_amount, 2) : '—' }}</td>
                            <td class="py-2.5 pl-3 pr-5 text-right tabular-nums font-medium {{ $s->difference !== null && (float) $s->difference < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $s->difference !== null ? '$'.number_format($s->difference, 2) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>
    @endif
</div>
@endsection
