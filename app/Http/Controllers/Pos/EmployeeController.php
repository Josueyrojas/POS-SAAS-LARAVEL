<?php

namespace App\Http\Controllers\Pos;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:200'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $businessId = Auth::user()->business_id;

        if (User::where('business_id', $businessId)->where('email', $data['email'])->exists()) {
            return back()->withErrors(['email' => 'Ya existe un usuario con ese correo en este negocio.'])->withInput();
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // cast 'hashed' lo encripta
            'role' => UserRole::EMPLOYEE->value,
            'business_id' => $businessId,
        ]);

        return back()->with('status', 'Empleado creado.');
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
