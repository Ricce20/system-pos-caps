<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersEmployee extends Model
{
    use SoftDeletes;

    protected $table = 'users_employees';

    protected $fillable = [
        'user_id',
        'employee_id',
        'online',
        'active',
        'start_date',
        'end_date',
        'deleted_at',
    ];

    /**
     * Relación con el modelo User.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el modelo Employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
