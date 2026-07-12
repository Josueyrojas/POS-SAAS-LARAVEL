<?php

namespace App\Http\Controllers\Auth;

use App\Enums\BusinessStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    // ---------------------------- PLATAFORMA -----------------------------

    public function showSuperAdminForm()
    {
        return view('auth.login');
    }

    public function superAdminLogin(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Solo usuarios SUPER_ADMIN entran por aquí. Un usuario de negocio no
        // puede colarse aunque acierte la contraseña.
        $ok = Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::SUPER_ADMIN->value,
            'is_active' => true,
        ]);

        if (! $ok) {
            throw ValidationException::withMessages([
                'email' => 'Correo o contraseña incorrectos.',
            ]);
        }

        $request->session()->regenerate();
        $this->touchLastLogin($request);

        return redirect()->intended(route('super-admin.businesses.index'));
    }

    // ------------------------------ NEGOCIO ------------------------------

    public function showBusinessForm(string $slug)
    {
        $business = Business::where('slug', $slug)
            ->where('status', BusinessStatus::ACTIVE->value)
            ->firstOrFail();

        return view('auth.business-login', compact('business'));
    }

    public function businessLogin(Request $request, string $slug)
    {
        $business = Business::where('slug', $slug)
            ->where('status', BusinessStatus::ACTIVE->value)
            ->firstOrFail();

        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Acotamos la búsqueda del usuario a ESTE negocio. Al filtrar por
        // business_id quedan excluidos los super admins (business_id null).
        $ok = Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password'],
            'business_id' => $business->id,
            'is_active' => true,
        ]);

        if (! $ok) {
            throw ValidationException::withMessages([
                'email' => 'Correo o contraseña incorrectos.',
            ]);
        }

        $request->session()->regenerate();
        $this->touchLastLogin($request);

        return redirect()->intended(route('pos.dashboard'));
    }

    // ------------------------------ LOGOUT -------------------------------

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function touchLastLogin(Request $request): void
    {
        $request->user()->forceFill(['last_login_at' => now()])->save();
    }
}
