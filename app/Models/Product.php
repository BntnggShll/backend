<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    use HasFactory;
    protected $table = 'products';
    protected $fillable = ['nama_produk', 'jenis_produk', 'harga', 'stock', 'image'];

    public function orderItems() {
        return $this->hasMany(OrderItem::class);
    }
}
