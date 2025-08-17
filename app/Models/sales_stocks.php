<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sales_stocks extends Model
{
    use HasFactory;

    protected $table = 'sales_stocks';
    protected $fillable = ['sales_id','quantity','status','product_unit_id','stock_movement_id'];

    public function sales()
    {
        return $this->belongsTo(User::class);
    }
    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }
    public function stockmovement()
    {
        return $this->belongsTo(StockMovement::class,'stock_movement_id');
    }
    
}
