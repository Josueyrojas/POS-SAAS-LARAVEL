<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\BusinessPlan;
use App\Enums\BusinessStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

class BusinessController extends Controller
{
    public function index()
    {
        // Sin contexto de inquilino: consultas GLOBALES (es el plano plataforma).
        $businesses = Business::query()
            ->withCount(['users', 'products', 'sales'])
            ->latest()
            ->get();

        $metrics = [
            'total' => $businesses->count(),
            'active' => $businesses->where('status', BusinessStatus::ACTIVE)->count(),
            'pending' => $businesses->where('status', BusinessStatus::PENDING)->count(),
            'suspended' => $businesses->where('status', BusinessStatus::SUSPENDED)->count(),
        ];

        return view('super-admin.businesses.index', compact('businesses', 'metrics'));
    }

    public function show(string $business)
    {
        $model = Business::withCount(['users', 'products', 'sales', 'branches'])
            ->findOrFail($business);

        $admins = $model->users()
            ->where('role', UserRole::BUSINESS_ADMIN->value)
            ->orderBy('created_at')
            ->get(['id', 'name', 'email', 'is_active', 'last_login_at']);

        return view('super-admin.businesses.show', ['business' => $model, 'admins' => $admins]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'plan' => ['required', new Enum(BusinessPlan::class)],
            'admin_name' => ['required', 'string', 'min:2', 'max:120'],
            'admin_email' => ['required', 'email', 'max:190'],
            'admin_password' => ['required', 'string', 'min:8', 'max:72'],
        ]);

        // Alta atómica: si el correo colisiona, no queda un negocio huérfano.
        DB::transaction(function () use ($data) {
            $business = Business::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'plan' => $data['plan'],
                'status' => BusinessStatus::ACTIVE->value,
            ]);

            User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'], // cast 'hashed' lo encripta
                'role' => UserRole::BUSINESS_ADMIN->value,
                'business_id' => $business->id,
            ]);
        });

        return redirect()
            ->route('super-admin.businesses.index')
            ->with('status', 'Negocio creado correctamente.');
    }

    public function updateStatus(Request $request, string $business)
    {
        $data = $request->validate(['status' => ['required', new Enum(BusinessStatus::class)]]);
        Business::findOrFail($business)->update(['status' => $data['status']]);

        return back()->with('status', 'Estado actualizado.');
    }

    public function updatePlan(Request $request, string $business)
    {
        $data = $request->validate(['plan' => ['required', new Enum(BusinessPlan::class)]]);
        Business::findOrFail($business)->update(['plan' => $data['plan']]);

        return back()->with('status', 'Plan actualizado.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'negocio';
        $slug = $base;
        $n = 1;
        while (Business::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }
}
