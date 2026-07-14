<?php

namespace App\Http\Controllers\Pos;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Product;
use App\Models\Sale;
use App\Notifications\LowStockAlertNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

        $this->maybeSendLowStockAlert($business);

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

    /**
     * No hay cron/queue worker en este despliegue (plan gratis de Render), así
     * que la alerta se dispara oportunistamente cada vez que alguien visita el
     * panel — máximo una vez al día por negocio (columna
     * low_stock_alert_sent_at), para no inundar de correos con cada visita.
     */
    private function maybeSendLowStockAlert(Business $business): void
    {
        $alreadySentToday = $business->low_stock_alert_sent_at?->gt(now()->subDay()) ?? false;
        if ($alreadySentToday) {
            return;
        }

        $allLowStock = Product::where('is_active', true)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('stock')
            ->get(['id', 'name', 'stock', 'stock_minimo']);

        if ($allLowStock->isEmpty()) {
            return;
        }

        $admins = $business->users()->where('role', UserRole::BUSINESS_ADMIN->value)->where('is_active', true)->get();
        if ($admins->isEmpty()) {
            return;
        }

        $productsUrl = route('pos.products.index');

        try {
            foreach ($admins as $admin) {
                $admin->notify(new LowStockAlertNotification($business->name, $allLowStock, $productsUrl));
            }
            $business->update(['low_stock_alert_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar la alerta de stock bajo.', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
