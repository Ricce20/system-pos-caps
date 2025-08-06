<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Filament\Panel;

class User extends Authenticatable implements JWTSubject, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'is_available',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Relación con la tabla pivote users_employees.
     */
    public function usersEmployees()
    {
        return $this->hasMany(UsersEmployee::class, 'user_id');
    }

    /**
     * Relación muchos a muchos con Employee a través de la tabla pivote users_employees.
     */
    // public function employee()
    // {
    //     return $this->belongsToMany(Employee::class, 'users_employees', 'user_id', 'employee_id')
    //                 ->withTimestamps()
    //                 ->withPivot(['online', 'active', 'deleted_at']);
    // }

    /**
     * Relación simple con Employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relación con CashRegister (usuario asignado a cajas).
     */
    public function cashRegisters()
    {
        return $this->hasMany(CashRegister::class);
    }

    /**
     * Verifica si el usuario es empleado.
     */
    public function isEmployee(): bool
    {
        return $this->role === 'empleado';
    }

    /**
     * Verifica si el usuario es administrador.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Verifica si el usuario tiene cajas asignadas.
     */
    public function hasAssignedCashRegisters(): bool
    {
        return $this->cashRegisters()->where('is_available', true)->exists();
    }

    /**
     * Verifica si el usuario es empleado y tiene cajas asignadas.
     */
    public function isEmployeeWithCashRegister(): bool
    {
        return $this->isEmployee() && $this->hasAssignedCashRegisters();
    }

    /**
     * Obtiene las cajas asignadas al usuario.
     */
    public function getAssignedCashRegisters()
    {
        return $this->cashRegisters()->where('is_available', true)->get();
    }

    /**
     * Verifica si el usuario puede acceder a una caja específica.
     */
    public function canAccessCashRegister(CashRegister $cashRegister): bool
    {
        return $this->isEmployee() && $this->id === $cashRegister->user_id;
    }

    /**
     * Verifica si el usuario tiene permisos de administrador.
     */
    public function hasAdminPermissions(): bool
    {
        return $this->isAdmin() || $this->role === 'supervisor';
    }
    
}
