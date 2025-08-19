<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = ['product_unit_id', 'quantity','quantity_sales'];

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class,'product_unit_id');
    }
}