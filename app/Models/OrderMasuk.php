<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderMasuk extends Model
{
    use HasFactory;
    protected $table = 'shipments';

    protected $fillable = (['status_pengiriman','sales_id']);

    public function shipment() {
        return $this->hasOne(Shipment::class,'order_id');
    }
    
}
