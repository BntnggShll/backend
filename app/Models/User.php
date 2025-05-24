<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Model
{
    use HasFactory;
    protected $table = 'users';
    protected $fillable = ['name', 'role', 'alamat', 'email', 'password'];

    public function orders() {
        return $this->hasMany(Order::class);
    }

    public function sale() {
        return $this->hasOne(Sales::class);
    }

    public function reseller() {
        return $this->hasOne(Reseller::class);
    }
}
