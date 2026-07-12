<form method="GET" class="mb-6 flex items-end gap-3">
    <div>
        <label class="mb-1 block text-xs font-medium text-slate-500">Desde</label>
        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}"
               class="rounded-md border border-slate-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none">
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-slate-500">Hasta</label>
        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}"
               class="rounded-md border border-slate-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none">
    </div>
    <button class="rounded-md border border-slate-300 px-3.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Filtrar</button>
</form>
