<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\BusinessPlan;
use App\Enums\BusinessStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\PasswordReset;
use App\Models\User;
use App\Notifications\WelcomeEmployeeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /**
     * El Super Admin ya no captura la contraseña del admin del negocio: se
     * crea con una contraseña aleatoria inservible y se le manda un correo
     * de bienvenida (mismo mecanismo que el alta de empleados) para que él
     * mismo la defina. Nunca circula una contraseña en texto plano.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'plan' => ['required', new Enum(BusinessPlan::class)],
            'admin_name' => ['required', 'string', 'min:2', 'max:120'],
            'admin_email' => ['required', 'email', 'max:190'],
        ]);

        // Alta atómica: si el correo colisiona, no queda un negocio huérfano.
        // El envío de correo NO va dentro de la transacción: es un efecto
        // externo, y si el proveedor de correo falla el negocio igual debe
        // quedar creado ("Reenviar invitación" es la vía de recuperación).
        [$business, $admin] = DB::transaction(function () use ($data) {
            $business = Business::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'plan' => $data['plan'],
                'status' => BusinessStatus::ACTIVE->value,
            ]);

            $admin = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Str::random(40), // inservible: el admin define la suya por correo
                'role' => UserRole::BUSINESS_ADMIN->value,
                'business_id' => $business->id,
            ]);

            return [$business, $admin];
        });

        $emailSent = $this->sendWelcomeEmail($admin, $business);

        $status = $emailSent
            ? 'Negocio creado. Se le envió un correo al administrador para que defina su contraseña.'
            : 'Negocio creado, pero el correo de bienvenida no se pudo enviar. Usa "Reenviar invitación" para intentar de nuevo.';

        return redirect()
            ->route('super-admin.businesses.index')
            ->with('status', $status);
    }

    /**
     * Por si el correo de bienvenida se perdió, fue a spam, expiró (60 min),
     * o falló al enviarse: solo tiene sentido mientras el admin nunca ha
     * iniciado sesión, es decir, mientras sigue con la contraseña aleatoria
     * inservible.
     */
    public function resendInvite(string $business, string $user)
    {
        $model = Business::findOrFail($business);
        $admin = $model->users()->where('role', UserRole::BUSINESS_ADMIN->value)->findOrFail($user);

        if ($admin->last_login_at !== null) {
            abort(422, 'Este administrador ya inició sesión; usa "Recuperar contraseña" en el login si la olvidó.');
        }

        $emailSent = $this->sendWelcomeEmail($admin, $model);

        return back()->with('status', $emailSent ? 'Invitación reenviada.' : 'No se pudo enviar el correo. Intenta de nuevo más tarde.');
    }

    private function sendWelcomeEmail(User $admin, Business $business): bool
    {
        PasswordReset::where('user_id', $admin->id)->delete();

        $plainToken = Str::random(64);
        PasswordReset::create([
            'user_id' => $admin->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(60),
        ]);

        $setPasswordUrl = route('password.business.reset', [$business->slug, $plainToken]);

        try {
            $admin->notify(new WelcomeEmployeeNotification($business->name, $setPasswordUrl));

            return true;
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de bienvenida al admin del negocio.', [
                'admin_id' => $admin->id,
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
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

    public function updateTaxRate(Request $request, string $business)
    {
        $data = $request->validate(['tax_rate' => ['required', 'numeric', 'min:0', 'max:100']]);
        Business::findOrFail($business)->update(['tax_rate' => $data['tax_rate']]);

        return back()->with('status', 'Tasa de IVA actualizada.');
    }

    /**
     * Borrado permanente e irreversible: TODO lo del negocio (ventas,
     * productos, usuarios, compras, etc.) se elimina en cascada a nivel de
     * base de datos (cascadeOnDelete en cada FK a businesses). Exige escribir
     * el nombre exacto del negocio para confirmar — no es un simple confirm(),
     * a diferencia de archivar/restaurar, esto no tiene vuelta atrás.
     */
    public function destroy(Request $request, string $business)
    {
        $model = Business::findOrFail($business);

        $data = $request->validate(['confirm_name' => ['required', 'string']]);

        if ($data['confirm_name'] !== $model->name) {
            return back()->withErrors(['confirm_name' => 'El nombre no coincide. Escribe el nombre exacto del negocio para confirmar.']);
        }

        $model->delete();

        return redirect()
            ->route('super-admin.businesses.index')
            ->with('status', 'Negocio eliminado permanentemente junto con todos sus datos.');
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
