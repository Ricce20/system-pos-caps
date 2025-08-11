<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_date',
        'employee_id',        // ← corregido
        'user_id',
        'location_id',
        'total',
        'is_check',
        'method_of_payment',
        'cash_register_id',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'total'     => 'decimal:2',
        'is_check'  => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class, 'sale_id');
    }

    /**
     * (Opcional) Acceso directo a los Items de la venta vía los detalles.
     */
    public function items()
    {
        return $this->hasManyThrough(
            Item::class,        // Modelo destino
            SaleDetail::class,  // Modelo intermedio
            'sale_id',          // FK en sale_details que apunta a sales.id
            'id',               // FK en items que se usa en sale_details.item_id (columna local en items)
            'id',               // Local key en sales
            'item_id'           // Local key en sale_details que apunta a items.id
        );
    }
}
