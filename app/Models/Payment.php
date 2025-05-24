<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;
    protected $table = 'payments';
    protected $fillable = ['order_id', 'jumlah', 'metode_pembayaran', 'status_pembayaran', 'tanggal_transaksi'];

    public function order() {
        return $this->belongsTo(Order::class);
    }
}
