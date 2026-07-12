<?php

namespace App\Http\Controllers\Auth;

use App\Enums\BusinessStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\PasswordReset;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * El login es 100% custom (sin Fortify/Breeze) y el email NO es único
 * globalmente (solo por negocio) — el Password broker estándar de Laravel
 * buscaría por email y podría chocar entre negocios. Por eso esto se
 * construye a mano, igual que LoginController, resolviendo el usuario
 * dentro del contexto correcto (super admin o el negocio del slug).
 */
class PasswordResetController extends Controller
{
    // ---------------------------- PLATAFORMA -----------------------------

    public function showSuperAdminForgotForm()
    {
        return view('auth.forgot-password', ['postUrl' => route('password.super-admin.email')]);
    }

    public function sendSuperAdminResetLink(Request $request)
    {
        return $this->sendResetLink($request, null);
    }

    public function showSuperAdminResetForm(string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'postUrl' => route('password.super-admin.update', $token)]);
    }

    public function resetSuperAdminPassword(Request $request, string $token)
    {
        return $this->resetPassword($request, $token, route('login'));
    }

    // ------------------------------ NEGOCIO ------------------------------

    public function showBusinessForgotForm(string $slug)
    {
        $business = Business::where('slug', $slug)->where('status', BusinessStatus::ACTIVE->value)->firstOrFail();

        return view('auth.forgot-password', ['postUrl' => route('password.business.email', $slug), 'business' => $business]);
    }

    public function sendBusinessResetLink(Request $request, string $slug)
    {
        return $this->sendResetLink($request, $slug);
    }

    public function showBusinessResetForm(string $slug, string $token)
    {
        $business = Business::where('slug', $slug)->where('status', BusinessStatus::ACTIVE->value)->firstOrFail();

        return view('auth.reset-password', [
            'token' => $token,
            'postUrl' => route('password.business.update', [$slug, $token]),
            'business' => $business,
        ]);
    }

    public function resetBusinessPassword(Request $request, string $slug, string $token)
    {
        $business = Business::where('slug', $slug)->where('status', BusinessStatus::ACTIVE->value)->firstOrFail();

        return $this->resetPassword($request, $token, route('business.login', $business->slug));
    }

    // ------------------------------ COMÚN --------------------------------

    private function sendResetLink(Request $request, ?string $slug)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $user = $this->resolveUser($data['email'], $slug);

        if ($user) {
            // Invalida cualquier solicitud anterior sin usar.
            PasswordReset::where('user_id', $user->id)->delete();

            $plainToken = Str::random(64);
            PasswordReset::create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addMinutes(60),
            ]);

            $resetUrl = $slug
                ? route('password.business.reset', [$slug, $plainToken])
                : route('password.super-admin.reset', $plainToken);

            $user->notify(new ResetPasswordNotification($resetUrl));
        }

        // Mismo mensaje exista o no el correo: no se revela si una cuenta existe.
        return back()->with('status', 'Si el correo existe, te enviamos un enlace para restablecer tu contraseña.');
    }

    private function resetPassword(Request $request, string $token, string $loginRoute)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $reset = PasswordReset::where('token_hash', hash('sha256', $token))->first();

        if (! $reset || $reset->isExpired()) {
            return back()->withErrors(['password' => 'El enlace no es válido o ya expiró. Solicita uno nuevo.']);
        }

        $user = User::findOrFail($reset->user_id);
        $user->update(['password' => $data['password']]); // cast 'hashed' lo encripta

        PasswordReset::where('user_id', $user->id)->delete();

        return redirect($loginRoute)->with('status', 'Contraseña actualizada. Ya puedes iniciar sesión.');
    }

    private function resolveUser(string $email, ?string $slug): ?User
    {
        if ($slug === null) {
            return User::where('email', $email)
                ->where('role', UserRole::SUPER_ADMIN->value)
                ->where('is_active', true)
                ->first();
        }

        $business = Business::where('slug', $slug)->where('status', BusinessStatus::ACTIVE->value)->first();
        if (! $business) {
            return null;
        }

        return User::where('email', $email)
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->first();
    }
}
