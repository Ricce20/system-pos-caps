<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseTransferDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_transfer_id',
        'item_id',
        'quantity',
    ];

    public function warehouseTransfer()
    {
        return $this->belongsTo(WarehouseTransfer::class);
    }

    public function item(){
        return $this->belongsTo(Item::class);
    }
}
