<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'stock',
        'is_available'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
