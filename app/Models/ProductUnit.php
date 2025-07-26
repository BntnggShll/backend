<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_id',
        'parent_id',
        'conversion_rate',
        'harga_jual',
        'is_base_unit',
        'min_stock_level',
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function orderitem()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function parent()
    {
        return $this->belongsTo(ProductUnit::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductUnit::class, 'parent_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}