<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTransaction extends Model
{
    use HasFactory;
    protected $table = 'sales_transactions';
    protected $fillable = ['sales_id', 'store_name', 'product_name', 'quantity', 'price', 'total', 'transaction_date'];

    public function sale() {
        return $this->belongsTo(Sale::class);
    }
}
