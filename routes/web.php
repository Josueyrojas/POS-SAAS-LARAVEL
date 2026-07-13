<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Pos\BranchController;
use App\Http\Controllers\Pos\BusinessSettingsController;
use App\Http\Controllers\Pos\CashSessionController;
use App\Http\Controllers\Pos\CategoryController;
use App\Http\Controllers\Pos\CustomerController;
use App\Http\Controllers\Pos\CustomerPaymentController;
use App\Http\Controllers\Pos\DashboardController;
use App\Http\Controllers\Pos\ProductController;
use App\Http\Controllers\Pos\EmployeeController;
use App\Http\Controllers\Pos\PurchaseController;
use App\Http\Controllers\Pos\ReportController;
use App\Http\Controllers\Pos\SaleController;
use App\Http\Controllers\Pos\SupplierController;
use App\Http\Controllers\SuperAdmin\BusinessController;
use Illuminate\Support\Facades\Route;

// ------------------------------ PÚBLICO --------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');

// Login de plataforma (Super Admin)
Route::get('/login', [LoginController::class, 'showSuperAdminForm'])->name('login');
Route::post('/login', [LoginController::class, 'superAdminLogin'])->middleware('throttle:login');

// Recuperación de contraseña — plataforma. Deben ir ANTES de /login/{slug}
// (mismo número de segmentos: si no, "forgot-password" se leería como slug).
Route::get('/login/forgot-password', [PasswordResetController::class, 'showSuperAdminForgotForm'])->name('password.super-admin.request');
Route::post('/login/forgot-password', [PasswordResetController::class, 'sendSuperAdminResetLink'])->middleware('throttle:login')->name('password.super-admin.email');
Route::get('/login/reset-password/{token}', [PasswordResetController::class, 'showSuperAdminResetForm'])->name('password.super-admin.reset');
Route::post('/login/reset-password/{token}', [PasswordResetController::class, 'resetSuperAdminPassword'])->name('password.super-admin.update');

// Login de negocio (acotado por slug)
Route::get('/login/{slug}', [LoginController::class, 'showBusinessForm'])->name('business.login');
Route::post('/login/{slug}', [LoginController::class, 'businessLogin'])->middleware('throttle:login');

// Recuperación de contraseña — negocio.
Route::get('/login/{slug}/forgot-password', [PasswordResetController::class, 'showBusinessForgotForm'])->name('password.business.request');
Route::post('/login/{slug}/forgot-password', [PasswordResetController::class, 'sendBusinessResetLink'])->middleware('throttle:login')->name('password.business.email');
Route::get('/login/{slug}/reset-password/{token}', [PasswordResetController::class, 'showBusinessResetForm'])->name('password.business.reset');
Route::post('/login/{slug}/reset-password/{token}', [PasswordResetController::class, 'resetBusinessPassword'])->name('password.business.update');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --------------------------- PLATAFORMA --------------------------------
Route::middleware(['auth', 'superadmin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/businesses', [BusinessController::class, 'index'])->name('businesses.index');
        Route::post('/businesses', [BusinessController::class, 'store'])->name('businesses.store');
        Route::get('/businesses/{business}', [BusinessController::class, 'show'])->name('businesses.show');
        Route::patch('/businesses/{business}/status', [BusinessController::class, 'updateStatus'])->name('businesses.status');
        Route::patch('/businesses/{business}/plan', [BusinessController::class, 'updatePlan'])->name('businesses.plan');
        Route::patch('/businesses/{business}/tax-rate', [BusinessController::class, 'updateTaxRate'])->name('businesses.tax-rate');
        Route::post('/businesses/{business}/admins/{user}/resend-invite', [BusinessController::class, 'resendInvite'])->name('businesses.admins.resend-invite');
        Route::delete('/businesses/{business}', [BusinessController::class, 'destroy'])->name('businesses.destroy');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

// ----------------------------- INQUILINO -------------------------------
// `business`: cualquier usuario del negocio (admin o empleado). `business.admin`:
// solo BUSINESS_ADMIN, para las secciones administrativas (catálogo de
// escritura, proveedores, compras). Ambos fijan/heredan el mismo TenantContext.
Route::middleware(['auth', 'business'])
    ->prefix('pos')
    ->name('pos.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');

        // Clientes: lectura/alta disponible para admin y empleado por igual.
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::patch('/customers/{customer}/active', [CustomerController::class, 'setActive'])->name('customers.active');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::post('/customers/{customer}/payments', [CustomerPaymentController::class, 'store'])->name('customer-payments.store');

        // Turno de caja: abrir/ver/cerrar es de ambos roles (cada cajero
        // maneja el suyo). Requerido antes de poder vender (ver abajo).
        Route::get('/cash-sessions/new', [CashSessionController::class, 'create'])->name('cash-sessions.create');
        Route::post('/cash-sessions', [CashSessionController::class, 'store'])->name('cash-sessions.store');
        Route::get('/cash-sessions/current', [CashSessionController::class, 'show'])->name('cash-sessions.show');
        Route::post('/cash-sessions/close', [CashSessionController::class, 'close'])->name('cash-sessions.close');

        // Ventas: vender y ver el propio historial es de ambos roles (el
        // controller filtra el historial del empleado a solo sus ventas).
        // `cash.session`: no se puede vender sin un turno de caja abierto.
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::middleware('cash.session')->group(function () {
            Route::get('/sales/new', [SaleController::class, 'create'])->name('sales.create');
            Route::get('/sales/products-search', [SaleController::class, 'productsSearch'])->name('sales.products-search');
            Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
        });
        // {sale} es un wildcard: debe ir DESPUÉS de /sales/new y /sales/products-search,
        // si no, "new" se interpreta como un id de venta (ya pasó una vez).
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');

        Route::middleware('business.admin')->group(function () {
            Route::patch('/sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');
            Route::patch('/sales/{sale}/refund', [SaleController::class, 'refund'])->name('sales.refund');

            Route::get('/cash-sessions', [CashSessionController::class, 'index'])->name('cash-sessions.index');

            Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::patch('/employees/{employee}/active', [EmployeeController::class, 'setActive'])->name('employees.active');
            Route::post('/employees/{employee}/resend-invite', [EmployeeController::class, 'resendInvite'])->name('employees.resend-invite');

            Route::post('/products', [ProductController::class, 'store'])->name('products.store');
            Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::patch('/products/{product}/active', [ProductController::class, 'setActive'])->name('products.active');
            Route::patch('/products/{product}/stock', [ProductController::class, 'adjustStock'])->name('products.stock');

            Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::patch('/categories/{category}/active', [CategoryController::class, 'setActive'])->name('categories.active');

            Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
            Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
            Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
            Route::patch('/suppliers/{supplier}/active', [SupplierController::class, 'setActive'])->name('suppliers.active');

            Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
            Route::get('/purchases/new', [PurchaseController::class, 'create'])->name('purchases.create');
            Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
            Route::patch('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
            Route::patch('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');

            Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
            Route::get('/reports/top-products', [ReportController::class, 'topProducts'])->name('reports.top-products');
            Route::get('/reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');
            Route::get('/reports/margin', [ReportController::class, 'margin'])->name('reports.margin');

            Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
            Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
            Route::patch('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
            Route::patch('/branches/{branch}/active', [BranchController::class, 'setActive'])->name('branches.active');

            Route::get('/settings', [BusinessSettingsController::class, 'edit'])->name('settings.edit');
            Route::patch('/settings', [BusinessSettingsController::class, 'update'])->name('settings.update');
        });
    });
