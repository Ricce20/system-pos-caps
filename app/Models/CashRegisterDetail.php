<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegisterDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'chas_register_id',
        'start_date',
        'end_date',
        'starting_quantity',
        'closing_amount',
    ];

    public function cash_register(){
        return $this->belongsTo(CashRegister::class);
    }

}
