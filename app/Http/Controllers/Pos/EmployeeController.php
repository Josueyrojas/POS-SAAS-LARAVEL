<?php

namespace App\Http\Controllers\Pos;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\PasswordReset;
use App\Models\User;
use App\Notifications\WelcomeEmployeeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * User NO usa BelongsToBusiness a propósito (ver App\Models\User): el login
 * necesita buscar por email antes de que exista contexto de inquilino, y el
 * Super Admin vive fuera de todo negocio. Por eso aquí SÍ filtramos a mano por
 * business_id — es la única excepción documentada a "nunca where business_id
 * manual" del resto del código.
 */
class EmployeeController extends Controller
{
    public function index()
    {
        $businessId = Auth::user()->business_id;

        $employees = User::where('business_id', $businessId)
            ->where('role', UserRole::EMPLOYEE->value)
            ->orderBy('name')
            ->get();

        return view('pos.employees.index', compact('employees'));
    }

    /**
     * El admin ya no captura la contraseña del empleado: se crea con una
     * contraseña aleatoria inservible y se le manda un correo de bienvenida
     * con un enlace (mismo mecanismo que recuperación de contraseña) para
     * que el propio empleado la defina. Así nunca circula una contraseña
     * en texto plano.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:200'],
        ]);

        $business = Auth::user()->business;

        if (User::where('business_id', $business->id)->where('email', $data['email'])->exists()) {
            return back()->withErrors(['email' => 'Ya existe un usuario con ese correo en este negocio.'])->withInput();
        }

        $employee = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Str::random(40), // inservible: nadie la conoce, el empleado define la suya por correo
            'role' => UserRole::EMPLOYEE->value,
            'business_id' => $business->id,
        ]);

        $emailSent = $this->sendWelcomeEmail($employee, $business);

        $status = $emailSent
            ? 'Empleado creado. Se le envió un correo para que defina su contraseña.'
            : 'Empleado creado, pero el correo de bienvenida no se pudo enviar. Usa "Reenviar invitación" para intentar de nuevo.';

        return back()->with('status', $status);
    }

    /**
     * Por si el correo de bienvenida se perdió, fue a spam, expiró (60 min),
     * o falló al enviarse: solo tiene sentido mientras el empleado nunca ha
     * iniciado sesión, es decir, mientras sigue con la contraseña aleatoria
     * inservible.
     */
    public function resendInvite(string $employee)
    {
        $model = $this->findEmployee($employee);

        if ($model->last_login_at !== null) {
            abort(422, 'Este empleado ya inició sesión; usa "Recuperar contraseña" en el login si la olvidó.');
        }

        $emailSent = $this->sendWelcomeEmail($model, Auth::user()->business);

        return back()->with('status', $emailSent ? 'Invitación reenviada.' : 'No se pudo enviar el correo. Intenta de nuevo más tarde.');
    }

    /**
     * El envío de correo no está dentro de la transacción de creación del
     * empleado: si el proveedor de correo falla (dominio no verificado,
     * caída del servicio, etc.) el empleado igual debe quedar creado —
     * "Reenviar invitación" es la vía de recuperación para ese caso.
     */
    private function sendWelcomeEmail(User $employee, Business $business): bool
    {
        // Invalida cualquier enlace anterior sin usar, igual que al recuperar contraseña.
        PasswordReset::where('user_id', $employee->id)->delete();

        $plainToken = Str::random(64);
        PasswordReset::create([
            'user_id' => $employee->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(60),
        ]);

        $setPasswordUrl = route('password.business.reset', [$business->slug, $plainToken]);

        try {
            $employee->notify(new WelcomeEmployeeNotification($business->name, $setPasswordUrl));

            return true;
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de bienvenida al empleado.', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function update(Request $request, string $employee)
    {
        $model = $this->findEmployee($employee);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:200', Rule::unique('users')->where('business_id', $model->business_id)->ignore($model->id)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $model->update($data);

        return back()->with('status', 'Empleado actualizado.');
    }

    public function setActive(Request $request, string $employee)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $this->findEmployee($employee)->update(['is_active' => $data['is_active']]);

        return back()->with('status', 'Empleado actualizado.');
    }

    private function findEmployee(string $id): User
    {
        return User::where('business_id', Auth::user()->business_id)
            ->where('role', UserRole::EMPLOYEE->value)
            ->findOrFail($id);
    }
}
