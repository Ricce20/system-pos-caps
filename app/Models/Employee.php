<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'name',
        'paternal_last_name',
        'maternal_last_name',
        'address',
        'phone',
        'active',
    ];
    
    /**
     * Relación con la tabla pivote users_employees.
     */
    public function usersEmployees()
    {
        return $this->hasMany(UsersEmployee::class, 'employee_id');
    }

    /**
     * Relación muchos a muchos con User a través de la tabla pivote users_employees.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'users_employees', 'employee_id', 'user_id')
                    ->withTimestamps()
                    ->withPivot(['online', 'active', 'deleted_at']);
    }
}
