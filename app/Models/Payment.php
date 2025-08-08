<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;
    protected $table = 'payments';
    protected $fillable = [
        'order_id',
        'midtrans_transaction_id',
        'midtrans_order_id',
        'snap_token',
        'total_pembayaran',
        'metode_pembayaran',
        'status_pembayaran',
        'raw_response',
    ];
    public function order() {
        return $this->belongsTo(Order::class,'order_id');
    }
    
    
}
