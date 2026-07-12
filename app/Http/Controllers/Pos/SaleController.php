<?php

namespace App\Http\Controllers\Pos;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['seller', 'customer'])
            ->when(! Auth::user()->isAdmin(), fn ($q) => $q->where('seller_id', Auth::id()))
            ->latest()
            ->get();

        return view('pos.sales.index', compact('sales'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('pos.sales.create', compact('customers'));
    }

    /** Búsqueda de productos para el carrito (solo feedback de UX: el precio real se recalcula en store()). */
    public function productsSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->with('unitOfMeasure')
            ->where('is_active', true)
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'ilike', "%{$q}%")
                ->orWhere('sku', 'ilike', "%{$q}%")))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'unit' => $p->unitOfMeasure->abbreviation,
                'allows_decimal' => $p->unitOfMeasure->allows_decimal,
                'retail_price' => (string) $p->retail_price,
                'wholesale_price' => $p->wholesale_price ? (string) $p->wholesale_price : null,
                'wholesale_min_qty' => $p->wholesale_min_qty ? (string) $p->wholesale_min_qty : null,
                'stock' => (string) $p->stock,
            ]);

        return response()->json($products);
    }

    /**
     * Registra la venta. Todo el precio (menudeo/mayoreo) y el descuento de
     * stock se recalculan server-side dentro de una transacción — nunca se
     * confía en lo que mandó el carrito del cliente. Si falta stock de
     * cualquier línea, se revierte toda la venta (no hay venta parcial).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        $sale = DB::transaction(function () use ($data) {
            // Bloqueo ordenado por id: evita deadlocks si dos cajeros venden
            // simultáneamente productos que se solapan.
            $productIds = collect($data['items'])->pluck('product_id')->unique()->sort()->values();

            $products = Product::with('unitOfMeasure')
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                abort(422, 'Uno o más productos ya no existen.');
            }

            $sale = Sale::create([
                'seller_id' => Auth::id(),
                'customer_id' => $data['customer_id'] ?? null,
                'status' => SaleStatus::COMPLETED,
                'payment_method' => $data['payment_method'],
                'total' => 0,
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $product = $products[$item['product_id']];
                $quantity = (float) $item['quantity'];

                if (! $product->unitOfMeasure->allows_decimal && floor($quantity) != $quantity) {
                    abort(422, "La unidad de \"{$product->name}\" no admite cantidades decimales.");
                }
                if ($quantity > (float) $product->stock) {
                    abort(422, "Stock insuficiente para \"{$product->name}\" (disponible: {$product->stock}).");
                }

                [$unitPrice, $priceType] = $product->priceFor($quantity);
                $lineTotal = round($unitPrice * $quantity, 2);
                $total += $lineTotal;

                $sale->items()->create([
                    'product_id' => $product->id,
                    'name_snapshot' => $product->name,
                    'unit_label_snapshot' => $product->unitOfMeasure->abbreviation,
                    'unit_price' => $unitPrice,
                    'price_type' => $priceType,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ]);

                $before = (float) $product->stock;
                $after = $before - $quantity;
                $product->update(['stock' => $after]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => StockMovementType::SALE,
                    'quantity' => -$quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_sale_id' => $sale->id,
                    'created_by' => Auth::id(),
                ]);
            }

            $changeDue = null;
            $tendered = $data['amount_tendered'] ?? null;
            if ($data['payment_method'] === PaymentMethod::CASH->value && $tendered !== null) {
                $changeDue = round($tendered - $total, 2);
            }

            $sale->update(['total' => $total, 'amount_tendered' => $tendered, 'change_due' => $changeDue]);

            return $sale;
        });

        return redirect()->route('pos.sales.show', $sale->id)->with('status', 'Venta registrada.');
    }

    public function show(string $sale)
    {
        $sale = Sale::with(['items.product', 'seller', 'customer', 'voidedBy', 'refundedBy'])->findOrFail($sale);

        if (! Auth::user()->isAdmin() && $sale->seller_id !== Auth::id()) {
            abort(403);
        }

        return view('pos.sales.show', compact('sale'));
    }

    public function void(Request $request, string $sale)
    {
        Gate::authorize('void-sale');
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->reverseStock($sale, function (Sale $sale) use ($data) {
            $sale->update([
                'status' => SaleStatus::VOIDED,
                'voided_at' => now(),
                'voided_by' => Auth::id(),
                'void_reason' => $data['reason'] ?? null,
            ]);
        });

        return back()->with('status', 'Venta anulada.');
    }

    public function refund(Request $request, string $sale)
    {
        Gate::authorize('refund-sale');
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->reverseStock($sale, function (Sale $sale) use ($data) {
            $sale->update([
                'status' => SaleStatus::REFUNDED,
                'refunded_at' => now(),
                'refunded_by' => Auth::id(),
                'refund_reason' => $data['reason'] ?? null,
            ]);
        });

        return back()->with('status', 'Venta reembolsada.');
    }

    /** Revierte el stock de una venta completada (usado por void() y refund()). */
    private function reverseStock(string $saleId, \Closure $markSale): void
    {
        DB::transaction(function () use ($saleId, $markSale) {
            $sale = Sale::with('items')->lockForUpdate()->findOrFail($saleId);

            if ($sale->status !== SaleStatus::COMPLETED) {
                abort(422, 'Solo se pueden anular o reembolsar ventas completadas.');
            }

            $productIds = $sale->items->pluck('product_id')->filter()->unique()->sort()->values();
            $products = Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($sale->items as $item) {
                $product = $products[$item->product_id] ?? null;
                if (! $product) {
                    continue; // producto borrado desde que se hizo la venta
                }

                $before = (float) $product->stock;
                $after = $before + (float) $item->quantity;
                $product->update(['stock' => $after]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => StockMovementType::SALE_VOID,
                    'quantity' => $item->quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_sale_id' => $sale->id,
                    'created_by' => Auth::id(),
                ]);
            }

            $markSale($sale);
        });
    }
}
