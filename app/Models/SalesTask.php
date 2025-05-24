<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesTask extends Model
{
    use HasFactory;
    protected $table = 'sales_tasks';
    protected $fillable = ['sales_id', 'jenis_tugas', 'deskripsi', 'status', 'shipment_id'];

    public function sales() {
        return $this->belongsTo(Sales::class);
    }

    public function shipment() {
        return $this->belongsTo(Shipment::class);
    }
}
