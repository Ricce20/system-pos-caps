<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegister extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_available',
        'location_id',
        'user_id'
    ];

    public function location(){
        return $this->belongsTo(Location::class);
    }

    public function cashRegisterDetail(){
        return $this->hasMany(CashRegisterDetail::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
    
}
