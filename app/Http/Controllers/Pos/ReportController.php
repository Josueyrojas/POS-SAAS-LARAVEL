<?php

namespace App\Http\Controllers\Pos;

use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        [$from, $to] = $this->range($request);

        $rows = Sale::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as sales_count, SUM(total) as total')
            ->where('status', SaleStatus::COMPLETED->value)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $summary = [
            'total' => $rows->sum('total'),
            'count' => $rows->sum('sales_count'),
            'avg_ticket' => $rows->sum('sales_count') > 0 ? $rows->sum('total') / $rows->sum('sales_count') : 0,
        ];

        $chart = [
            'labels' => $rows->map(fn ($r) => Carbon::parse($r->day)->format('d/m'))->values(),
            'totals' => $rows->map(fn ($r) => round((float) $r->total, 2))->values(),
        ];

        return view('pos.reports.sales', compact('rows', 'summary', 'chart', 'from', 'to'));
    }

    public function topProducts(Request $request)
    {
        [$from, $to] = $this->range($request);

        $rows = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::COMPLETED->value)
            ->whereBetween('sales.created_at', [$from, $to])
            ->selectRaw('sale_items.product_id, sale_items.name_snapshot, SUM(sale_items.quantity) as qty, SUM(sale_items.line_total) as revenue')
            ->groupBy('sale_items.product_id', 'sale_items.name_snapshot')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $chart = [
            'labels' => $rows->pluck('name_snapshot')->values(),
            'totals' => $rows->map(fn ($r) => round((float) $r->revenue, 2))->values(),
        ];

        return view('pos.reports.top-products', compact('rows', 'chart', 'from', 'to'));
    }

    public function lowStock()
    {
        $products = Product::with(['category', 'unitOfMeasure'])
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('stock')
            ->get();

        return view('pos.reports.low-stock', compact('products'));
    }

    public function margin(Request $request)
    {
        [$from, $to] = $this->range($request);

        $rows = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', SaleStatus::COMPLETED->value)
            ->whereBetween('sales.created_at', [$from, $to])
            ->selectRaw('products.id, products.name, products.cost_price,
                SUM(sale_items.quantity) as qty,
                SUM(sale_items.line_total) as revenue,
                SUM(sale_items.quantity * COALESCE(products.cost_price, 0)) as cost')
            ->groupBy('products.id', 'products.name', 'products.cost_price')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($r) {
                $r->margin = $r->revenue - $r->cost;
                $r->has_cost = $r->cost_price !== null;

                return $r;
            });

        $summary = [
            'revenue' => $rows->sum('revenue'),
            'cost' => $rows->sum('cost'),
            'margin' => $rows->sum('revenue') - $rows->sum('cost'),
        ];

        return view('pos.reports.margin', compact('rows', 'summary', 'from', 'to'));
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function range(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->subDays(29)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();

        return [$from, $to];
    }
}
