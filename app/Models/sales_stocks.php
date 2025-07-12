<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sales_stocks extends Model
{
    use HasFactory;

    protected $table = 'sales_stocks';
    protected $fillable = ['sale_id','nama_produk','nam_unit','harga_jual','quantity','status'];

    public function sales()
    {
        return $this->belongsTo(User::class);
    }
}
