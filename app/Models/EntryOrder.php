<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntryOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'date_order',
        'notes',
        'total'
    ];


    // Relación con el proveedor
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Relación con el usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con los detalles de la orden de entrada (EntryOrderDetails)
    public function entryOrderDetail()
    {
        return $this->hasMany(EntryOrderDetail::class);
    }
}
