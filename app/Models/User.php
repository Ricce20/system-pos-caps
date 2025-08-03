<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
    
}
