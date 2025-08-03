<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'brand_id',
        'model_cap_id',
        'size_id',
        'category_id',
        'image_1',
        'image_2',
        'image_3',
        'is_available'
        // Agrega aquí otros campos si es necesario
    ];

    protected $casts = [
        'image_1' =>'string',
        'image_2' => 'string',
        'image_3' => 'string'
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function modelCap()
    {
        return $this->belongsTo(ModelCap::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }
    
    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function item()
    {
        return $this->hasMany(Item::class);
    }
}
