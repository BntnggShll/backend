<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable 
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
