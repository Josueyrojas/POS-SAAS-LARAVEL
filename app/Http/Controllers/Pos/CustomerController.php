<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $includeArchived = $request->query('archived') === '1';

        $customers = Customer::query()
            ->when(! $includeArchived, fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        return view('pos.customers.index', compact('customers', 'includeArchived'));
    }

    public function store(Request $request)
    {
        Customer::create($this->validated($request));

        return back()->with('status', 'Cliente creado.');
    }

    public function update(Request $request, string $customer)
    {
        Customer::findOrFail($customer)->update($this->validated($request));

        return back()->with('status', 'Cliente actualizado.');
    }

    public function setActive(Request $request, string $customer)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        Customer::findOrFail($customer)->update(['is_active' => $data['is_active']]);

        return back()->with('status', 'Cliente actualizado.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:200'],
            'document_id' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Vacío -> null (evita colisión en UNIQUE [business_id, document_id]).
        $data['document_id'] = $data['document_id'] !== null && $data['document_id'] !== '' ? $data['document_id'] : null;

        return $data;
    }
}
