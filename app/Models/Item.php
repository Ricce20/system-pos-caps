<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'product_id',
        'size_id',
        // 'price',
        'qr',
        'barcode',
        'code',
        'is_available'
    ];
    // Relación con Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relación con Size
    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    // Relación con SupplierItem
    public function supplierItem()
    {
        return $this->hasMany(SupplierItem::class);
    }

    public function warehouseItem(){
        return $this->hasMany(WarehouseItem::class);
    }

    public function saleDetail(){
        return $this->hasMany(SaleDetail::class);
    }

    public function getFullNameAttribute()
    {
        return $this->product->name . ' - ' . $this->size->name;
    }

}
