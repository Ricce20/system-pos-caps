<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationUser extends Model
{
    use SoftDeletes;
    
    protected $table = 'location_users';

    protected $fillable = [
        'location_id',
        'user_id',
        'activo',
        'start_date',
        'end_date',
    ];

    /**
     * Relación con la ubicación.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Relación con el usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
