<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'location',
        'active',
        'is_primary'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    public function warehouseItems(){
        return $this->hasMany(WarehouseItem::class);
    }

    public function location(){
        return $this->belongsTo(Location::class);
    }

    public function transfersAsSource()
    {
        return $this->hasMany(WarehouseTransfer::class, 'source_warehouse_id');
    }

    public function transfersAsDestination()
    {
        return $this->hasMany(WarehouseTransfer::class, 'destination_warehouse_id');
    }

    // public function warehouseTransfer(){
    //     return $this->hasMany(WarehouseTransfer::class);
    // }

    

}
