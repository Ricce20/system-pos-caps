<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierItem extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'supplier_id',
        'item_id',
        'is_available',
        'purchase_price',
        'sale_price',
        'is_primary'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
