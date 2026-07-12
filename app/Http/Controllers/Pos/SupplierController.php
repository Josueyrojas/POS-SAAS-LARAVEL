<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $includeArchived = $request->query('archived') === '1';

        $suppliers = Supplier::query()
            ->when(! $includeArchived, fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        return view('pos.suppliers.index', compact('suppliers', 'includeArchived'));
    }

    public function store(Request $request)
    {
        Supplier::create($this->validated($request));

        return back()->with('status', 'Proveedor creado.');
    }

    public function update(Request $request, string $supplier)
    {
        Supplier::findOrFail($supplier)->update($this->validated($request));

        return back()->with('status', 'Proveedor actualizado.');
    }

    public function setActive(Request $request, string $supplier)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        Supplier::findOrFail($supplier)->update(['is_active' => $data['is_active']]);

        return back()->with('status', 'Proveedor actualizado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'contact_name' => ['nullable', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:200'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
