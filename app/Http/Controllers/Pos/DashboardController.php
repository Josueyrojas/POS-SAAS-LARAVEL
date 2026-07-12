<?php

namespace App\Http\Controllers\Pos;

use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Product ya viene filtrado por el negocio activo (global scope).
        $active = Product::where('is_active', true)->get(['stock', 'stock_minimo']);

        $stats = [
            'active' => $active->count(),
            'low' => $active->filter(fn ($p) => $p->stock > 0 && $p->stock <= $p->stock_minimo)->count(),
            'out' => $active->where('stock', 0)->count(),
        ];

        $lowStockProducts = Product::where('is_active', true)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('stock')
            ->limit(5)
            ->get(['id', 'name', 'stock', 'stock_minimo']);

        $business = Auth::user()->business;

        // Las cifras de venta son financieras: solo el admin las ve, igual
        // que /pos/reports/*. El panel de stock bajo de arriba sí es de
        // ambos roles (es inventario, no dinero).
        $salesChart = null;
        if (Auth::user()->isAdmin()) {
            $from = now()->subDays(6)->startOfDay();
            $rows = Sale::selectRaw('DATE(created_at) as day, SUM(total) as total')
                ->where('status', SaleStatus::COMPLETED->value)
                ->whereBetween('created_at', [$from, now()->endOfDay()])
                ->groupBy('day')
                ->pluck('total', 'day');

            $labels = [];
            $totals = [];
            for ($d = $from->copy(); $d->lte(now()); $d->addDay()) {
                $labels[] = $d->format('d/m');
                $totals[] = round((float) ($rows[$d->format('Y-m-d')] ?? 0), 2);
            }

            $salesChart = ['labels' => $labels, 'totals' => $totals];
        }

        return view('pos.dashboard', compact('stats', 'business', 'lowStockProducts', 'salesChart'));
    }
}
