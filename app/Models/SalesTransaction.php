<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTransaction extends Model
{
    use HasFactory;
    protected $table = 'sales_transactions';
    protected $fillable = ['sales_id', 'nama_toko', 'nama_produk', 'jumlah', 'harga', 'total', 'tanggal_transaksi'];

    public function sale() {
        return $this->belongsTo(Sales::class);
    }
}
