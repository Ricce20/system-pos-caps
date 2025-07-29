<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_id',
        'item_id',
        'quantity',
        'subtotal'
    ];

    public function sale(){
        return $this->belongsTo(Sale::class);
    }

    public function item(){
        return $this->belongsTo(Item::class);
    }
}
