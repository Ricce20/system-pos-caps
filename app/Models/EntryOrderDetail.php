<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntryOrderDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'entry_order_id',
        'item_id',
        'quantity',
        'subtotal',
        'amount_of_waste',
    ];

    // Relación con EntryOrder
    public function entryOrder()
    {
        return $this->belongsTo(EntryOrder::class);
    }

    // Relación con Item
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
