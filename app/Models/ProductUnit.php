<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

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
        return $this->belongsTo(Product::class, 'product_id');
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
        return $this->hasMany(StockMovement::class, 'product_unit_id');
    }
    public function inventory()
    {
        return $this->hasOne(Inventory::class,'product_unit_id');
    }
    public function salesstockMovements()
    {
        return $this->hasMany(sales_stocks::class);
    }




}