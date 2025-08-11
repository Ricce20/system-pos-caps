<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes;

    // Habilitamos esta función necesaria para Filament
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // O puedes poner tu lógica de roles aquí, ej: return $this->isAdmin();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- MÉTODOS PARA JWT (ESTO ESTÁ CORRECTO) ---
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // --- El resto de tus relaciones y funciones ---
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function cashRegisters()
    {
        return $this->hasMany(CashRegister::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}