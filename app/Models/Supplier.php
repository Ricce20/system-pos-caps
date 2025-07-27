<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'name',
        'address',
        'phone',
        'brand_id',
        'is_available',
    ];

    public function brand(){
        return $this->belongsTo(Brand::class);
    }
    public function supplierItems()
    {
        return $this->hasMany(SupplierItem::class);
    }
}
