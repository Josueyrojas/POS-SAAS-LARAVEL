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
    /** Columnas del CSV de importación/exportación, en orden — mismo formato en ambos sentidos. */
    private const CSV_COLUMNS = [
        'sku', 'name', 'description', 'category', 'unit_of_measure',
        'retail_price', 'wholesale_price', 'wholesale_min_qty', 'cost_price',
        'stock', 'stock_minimo', 'is_active',
    ];

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

    /** Exporta TODOS los productos (activos y archivados) del negocio — sirve también como plantilla para importar. */
    public function export()
    {
        $products = Product::with(['category', 'unitOfMeasure'])->orderBy('name')->get();

        $filename = 'productos-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($products) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel abre el UTF-8 con acentos correctamente.
            fputcsv($out, self::CSV_COLUMNS);

            foreach ($products as $p) {
                fputcsv($out, [
                    $p->sku,
                    $p->name,
                    $p->description,
                    $p->category->name ?? '',
                    $p->unitOfMeasure->name,
                    $p->retail_price,
                    $p->wholesale_price,
                    $p->wholesale_min_qty,
                    $p->cost_price,
                    $p->stock,
                    $p->stock_minimo,
                    $p->is_active ? '1' : '0',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function importForm()
    {
        return view('pos.products.import');
    }

    /**
     * Importa por CSV para migrar de otra plataforma. Nunca falla todo el
     * lote por una fila mala: cada fila se procesa aparte y se reporta el
     * resultado (creado/actualizado/error) — un catálogo de 300 productos
     * con 2 filas mal escritas no debe rechazarse completo.
     */
    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $rows = $this->parseCsv($request->file('file')->getRealPath());
        $businessId = Auth::user()->business_id;

        $results = [];
        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // +1 por índice base 0, +1 por la fila de encabezado.
            $name = trim((string) ($row['name'] ?? ''));

            try {
                if ($name === '') {
                    throw new \RuntimeException('Falta el nombre.');
                }

                $retailPrice = $this->parseDecimal($row['retail_price'] ?? null);
                if ($retailPrice === null) {
                    throw new \RuntimeException('Falta o es inválido el precio de menudeo.');
                }

                $unit = $this->resolveUnitOfMeasure(trim((string) ($row['unit_of_measure'] ?? '')));
                if (! $unit) {
                    $valid = UnitOfMeasure::orderBy('name')->pluck('name')->implode(', ');
                    throw new \RuntimeException("Unidad de medida no reconocida. Válidas: {$valid}.");
                }

                $categoryId = $this->resolveOrCreateCategory(trim((string) ($row['category'] ?? '')), $businessId);

                $sku = trim((string) ($row['sku'] ?? ''));
                $sku = $sku !== '' ? $sku : null;

                $data = [
                    'name' => $name,
                    'description' => trim((string) ($row['description'] ?? '')) ?: null,
                    'sku' => $sku,
                    'category_id' => $categoryId,
                    'unit_of_measure_id' => $unit->id,
                    'retail_price' => $retailPrice,
                    'wholesale_price' => $this->parseDecimal($row['wholesale_price'] ?? null),
                    'wholesale_min_qty' => $this->parseDecimal($row['wholesale_min_qty'] ?? null),
                    'cost_price' => $this->parseDecimal($row['cost_price'] ?? null),
                    'stock' => $this->parseDecimal($row['stock'] ?? null) ?? 0,
                    'stock_minimo' => $this->parseDecimal($row['stock_minimo'] ?? null) ?? 0,
                    'is_active' => ! in_array(trim((string) ($row['is_active'] ?? '1')), ['0', 'false', 'no'], true),
                ];

                if (! $unit->allows_decimal && floor($data['stock']) != $data['stock']) {
                    throw new \RuntimeException("La unidad \"{$unit->name}\" no admite stock con decimales.");
                }

                // Upsert por SKU: permite re-subir el mismo archivo para
                // corregir precios/stock sin duplicar productos.
                $existing = $sku ? Product::where('sku', $sku)->first() : null;

                if ($existing) {
                    $existing->update($data);
                    $results[] = ['row' => $rowNumber, 'name' => $name, 'status' => 'actualizado'];
                } else {
                    Product::create($data);
                    $results[] = ['row' => $rowNumber, 'name' => $name, 'status' => 'creado'];
                }
            } catch (\Throwable $e) {
                $results[] = ['row' => $rowNumber, 'name' => $name !== '' ? $name : '(sin nombre)', 'status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return redirect()->route('pos.products.import.form')->with('importResults', $results);
    }

    /** Lee el CSV subido detectando ; o , como delimitador y quitando el BOM de Excel. */
    private function parseCsv(string $path): array
    {
        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $firstLine = strtok($content, "\n");
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $lines = array_filter(preg_split('/\r\n|\r|\n/', $content), fn ($l) => trim($l) !== '');
        if (empty($lines)) {
            return [];
        }

        $handle = fopen('php://memory', 'r+');
        foreach ($lines as $line) {
            fwrite($handle, $line."\n");
        }
        rewind($handle);

        $headerRow = fgetcsv($handle, 0, $delimiter) ?: [];
        $header = array_map(function ($h) {
            $h = preg_replace('/[^a-z0-9]+/', '_', trim(strtolower($h)));

            return trim($h, '_');
        }, $headerRow);

        $rows = [];
        while (($fields = fgetcsv($handle, 0, $delimiter)) !== false) {
            $len = min(count($header), count($fields));
            $rows[] = array_combine(array_slice($header, 0, $len), array_slice($fields, 0, $len));
        }
        fclose($handle);

        return $rows;
    }

    private function parseDecimal(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Formato latino común en CSVs de Excel/es-GT: "1.234,50" -> "1234.50".
        if (preg_match('/^-?\d{1,3}(\.\d{3})*(,\d+)?$/', $value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? $value : null;
    }

    private function resolveUnitOfMeasure(string $name): ?UnitOfMeasure
    {
        if ($name === '') {
            return null;
        }

        return UnitOfMeasure::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orWhereRaw('LOWER(abbreviation) = ?', [mb_strtolower($name)])
            ->first();
    }

    private function resolveOrCreateCategory(string $name, string $businessId): ?string
    {
        if ($name === '') {
            return null;
        }

        return Category::firstOrCreate(
            ['business_id' => $businessId, 'name' => $name],
            ['business_id' => $businessId, 'is_active' => true],
        )->id;
    }

    private function validated(Request $request, ?string $productId = null): array
    {
        $businessId = Auth::user()->business_id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sku' => ['nullable', 'string', 'max:64'],
            // `unit_of_measure_id` es un catálogo GLOBAL (sin business_id):
            // Rule::exists sin scope es correcto ahí. `category_id` SÍ es de
            // negocio — sin el ->where(business_id) aceptaría el id de una
            // categoría de OTRO negocio.
            'category_id' => ['nullable', 'uuid', Rule::exists('categories', 'id')->where('business_id', $businessId)],
            'unit_of_measure_id' => ['required', 'uuid', Rule::exists('units_of_measure', 'id')],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            // min:0.01 (no 0): con 0, priceFor() aplicaría mayoreo a
            // cualquier cantidad (`$quantity >= 0` siempre es true).
            'wholesale_min_qty' => ['nullable', 'numeric', 'min:0.01'],
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
