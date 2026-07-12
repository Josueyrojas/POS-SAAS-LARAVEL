<?php

namespace App\Http\Controllers\Pos;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Todas las consultas de Product quedan acotadas al negocio activo por el global
 * scope (el middleware `business` fijó el contexto). Nunca escribimos
 * `where('business_id', ...)` a mano: por eso es imposible, por olvido, tocar el
 * inventario de otro negocio. Un id de otro negocio simplemente no aparece y
 * `findOrFail` lanza 404.
 */
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $includeArchived = $request->query('archived') === '1';

        $products = Product::query()
            ->with(['category', 'unitOfMeasure'])
            ->when(! $includeArchived, fn ($q) => $q->where('is_active', true))
            ->latest()
            ->get();

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $units = UnitOfMeasure::orderBy('name')->get();

        return view('pos.products.index', compact('products', 'includeArchived', 'categories', 'units'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Product::create($data); // business_id se inyecta solo

        return back()->with('status', 'Producto creado.');
    }

    public function update(Request $request, string $product)
    {
        $model = Product::findOrFail($product); // acotado al negocio activo
        $model->update($this->validated($request, $model->id));

        return back()->with('status', 'Producto actualizado.');
    }

    public function setActive(Request $request, string $product)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        Product::findOrFail($product)->update(['is_active' => $data['is_active']]);

        return back()->with('status', 'Producto actualizado.');
    }

    /**
     * Ajuste manual de stock (+/-). Atómico: bloquea la fila, calcula el nuevo
     * stock y deja rastro en stock_movements — nunca se toca `products.stock`
     * directamente sin registrar de dónde vino el cambio.
     */
    public function adjustStock(Request $request, string $product)
    {
        $data = $request->validate(['delta' => ['required', 'numeric']]);

        DB::transaction(function () use ($product, $data) {
            $model = Product::with('unitOfMeasure')->lockForUpdate()->findOrFail($product);

            $delta = (float) $data['delta'];
            if (! $model->unitOfMeasure->allows_decimal && floor($delta) != $delta) {
                abort(422, 'Esta unidad de medida no admite cantidades decimales.');
            }

            $before = (float) $model->stock;
            $after = max(0, $before + $delta);
            $model->update(['stock' => $after]);

            StockMovement::create([
                'product_id' => $model->id,
                'type' => $delta >= 0 ? StockMovementType::MANUAL_IN : StockMovementType::MANUAL_OUT,
                'quantity' => $after - $before,
                'stock_before' => $before,
                'stock_after' => $after,
                'created_by' => Auth::id(),
            ]);
        });

        return back();
    }

    private function validated(Request $request, ?string $productId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['nullable', 'string', 'max:64'],
            'category_id' => ['nullable', 'uuid', Rule::exists('categories', 'id')],
            'unit_of_measure_id' => ['required', 'uuid', Rule::exists('units_of_measure', 'id')],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_min_qty' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
        ]);

        // SKU vacío -> null (evita colisión en UNIQUE [business_id, sku]).
        $data['sku'] = $data['sku'] !== null && $data['sku'] !== '' ? $data['sku'] : null;
        $data['stock_minimo'] = $data['stock_minimo'] ?? 0;

        $unit = UnitOfMeasure::findOrFail($data['unit_of_measure_id']);
        if (! $unit->allows_decimal && floor($data['stock']) != $data['stock']) {
            abort(422, 'La unidad "'.$unit->name.'" no admite cantidades decimales de stock.');
        }

        return $data;
    }
}
