<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = 'orders';
    protected $fillable = ['user_id', 'total_harga','shipping_cost'];
   
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function orderItems() {
        return $this->hasMany(OrderItem::class,'order_id');
    }

    public function shipment() {
        return $this->hasOne(Shipment::class);
    }

    public function payments() {
        return $this->hasMany(Payment::class,'order_id','id');
    }
    
}
