<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegisterDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cash_register_id',
        'start_date',
        'end_date',
        'starting_quantity',
        'closing_amount',
        'counted_amount'
    ];

    public function cashRegister(){
        return $this->belongsTo(CashRegister::class);
    }

}
