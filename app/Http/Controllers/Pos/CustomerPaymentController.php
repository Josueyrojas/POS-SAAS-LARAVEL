<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerPaymentController extends Controller
{
    public function store(Request $request, string $customer)
    {
        $model = Customer::findOrFail($customer);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(['CASH', 'CARD', 'TRANSFER'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // El `max` del HTML es solo cosmético: el servidor es la única
        // fuente de verdad. Sin esto, un abono mayor al saldo deja un
        // creditBalance() negativo sin sentido de negocio.
        if ($data['amount'] > $model->creditBalance()) {
            return back()->withErrors(['amount' => 'El abono no puede ser mayor al saldo pendiente.'])->withInput();
        }

        CustomerPayment::create([
            'customer_id' => $model->id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return back()->with('status', 'Abono registrado.');
    }
}
