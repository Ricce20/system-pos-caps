<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes,HasFactory;

    protected $fillable = [
        'name',
        'is_available'
        // Agrega aquí otros campos si es necesario
    ];

    public function suppliers(){
        return $this->hasMany(Supplier::class);
    }
}
