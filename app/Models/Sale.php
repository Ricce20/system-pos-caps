<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_date',
        'emplooye_id',
        'user_id',
        'location_id',
        'total',
        'is_check',
        'method_of_payment',
        'cash_register_id'
    ];

    public function employee(){
        return $this->belongsTo(Employee::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function location(){
        return $this->belongsTo(Location::class);
    }

    public function saleDetail(){
        return $this->hasMany(SaleDetail::class);
    }

    public function cashRegister(){
        return $this->belongsTo(CashRegister::class);
    }

   
}
