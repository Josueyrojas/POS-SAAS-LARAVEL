<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Un solo controller para el perfil propio, usado por dos rutas (pos.profile.*
 * con layouts.pos, super-admin.profile.* con layouts.platform) — mismo patrón
 * de "dos flujos, un controller" que PasswordResetController.
 */
class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $view = $user->isSuperAdmin() ? 'profile.edit-platform' : 'profile.edit-pos';

        return view($view, ['user' => $user]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $emailRule = $user->isSuperAdmin()
            ? Rule::unique('users')->where('role', UserRole::SUPER_ADMIN->value)->ignore($user->id)
            : Rule::unique('users')->where('business_id', $user->business_id)->ignore($user->id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:200', $emailRule],
            'current_password' => ['nullable', 'required_with:password', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (! empty($data['password'])) {
            if (! Hash::check($data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.'])->withInput();
            }
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = $data['password']; // cast 'hashed' lo encripta
        }
        $user->save();

        return back()->with('status', 'Perfil actualizado.');
    }
}
