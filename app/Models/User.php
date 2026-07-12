<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario. Deliberadamente NO lleva el global scope de inquilino: el sistema de
 * autenticación consulta la tabla `users` por email antes de que exista un
 * contexto de inquilino, y el Super Admin (business_id null) debe poder existir
 * fuera de todo negocio. La gestión de empleados por negocio se filtra a mano.
 */
class User extends Authenticatable
{
    use HasUuids, Notifiable, HasFactory;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'business_id', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'role' => UserRole::class,
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function business(): BelongsTo { return $this->belongsTo(Business::class); }

    public function isSuperAdmin(): bool { return $this->role === UserRole::SUPER_ADMIN; }

    public function isAdmin(): bool { return $this->role === UserRole::BUSINESS_ADMIN; }
}
