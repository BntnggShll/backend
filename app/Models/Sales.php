<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sales extends Model
{
    use HasFactory;
    protected $table = 'sales';
    protected $fillable = ['tanggal_bergabung', 'alamat', 'user_id'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function salesTasks() {
        return $this->hasMany(SalesTask::class);
    }

    public function salesTransactions() {
        return $this->hasMany(SalesTransaction::class);
    }
}
