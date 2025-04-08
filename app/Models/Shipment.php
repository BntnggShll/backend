<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;
    protected $table = 'shipments';
    protected $fillable = ['order_id', 'estimated_delivery', 'shipping_status'];

    public function order() {
        return $this->belongsTo(Order::class);
    }

}
