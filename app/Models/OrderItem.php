<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $table = 'order_items';
    protected $fillable = ['order_id', 'product_unit_id ', 'jumlah', 'harga'];

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function productunit() {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
}
