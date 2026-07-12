<?php

namespace App\Http\Controllers\Pos;

use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('supplier')->latest('purchase_date')->latest()->get();

        return view('pos.purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->with('unitOfMeasure')->orderBy('name')->get();

        return view('pos.purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'uuid', 'exists:suppliers,id'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');

            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'invoice_number' => $data['invoice_number'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'notes' => $data['notes'] ?? null,
                'status' => PurchaseStatus::PENDING,
                'total' => 0,
                'created_by' => Auth::id(),
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $product = $products[$item['product_id']];
                $lineTotal = round($item['quantity'] * $item['unit_cost'], 2);
                $total += $lineTotal;

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'name_snapshot' => $product->name,
                    'unit_cost' => $item['unit_cost'],
                    'quantity' => $item['quantity'],
                    'line_total' => $lineTotal,
                ]);
            }

            $purchase->update(['total' => $total]);
        });

        return redirect()->route('pos.purchases.index')->with('status', 'Compra registrada como pendiente.');
    }

    /**
     * Recibe la mercancía: sube el stock de cada línea de forma atómica
     * (lockForUpdate, ordenado por product_id para evitar deadlocks entre
     * recepciones concurrentes), actualiza el costo del producto al último
     * recibido y deja rastro en stock_movements.
     */
    public function receive(string $purchase)
    {
        DB::transaction(function () use ($purchase) {
            $purchase = Purchase::with('items')->lockForUpdate()->findOrFail($purchase);

            if ($purchase->status !== PurchaseStatus::PENDING) {
                abort(422, 'Esta compra ya no está pendiente.');
            }

            $items = $purchase->items->sortBy('product_id');
            $products = Product::whereIn('id', $items->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products[$item->product_id] ?? null;
                if (! $product) {
                    continue; // producto borrado desde que se levantó la compra
                }

                $before = (float) $product->stock;
                $after = $before + (float) $item->quantity;
                $product->update(['stock' => $after, 'cost_price' => $item->unit_cost]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => StockMovementType::PURCHASE,
                    'quantity' => $item->quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_purchase_id' => $purchase->id,
                    'created_by' => Auth::id(),
                ]);
            }

            $purchase->update(['status' => PurchaseStatus::RECEIVED]);
        });

        return back()->with('status', 'Compra recibida: stock actualizado.');
    }

    public function cancel(string $purchase)
    {
        $model = Purchase::findOrFail($purchase);
        if ($model->status !== PurchaseStatus::PENDING) {
            abort(422, 'Solo se pueden cancelar compras pendientes.');
        }
        $model->update(['status' => PurchaseStatus::CANCELED]);

        return back()->with('status', 'Compra cancelada.');
    }
}
