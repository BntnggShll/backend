<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sales extends Model
{
    use HasFactory;
    protected $table = 'users';
    protected $fillable = ['name', 'role', 'alamat', 'email', 'password'];

    public function salesTasks() {
        return $this->hasMany(SalesTask::class);
    }

    public function salesTransactions() {
        return $this->hasMany(SalesTransaction::class);
    }
}
