<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable 
{
    
    use HasFactory,Notifiable;
    protected $table = 'users';
    protected $fillable = ['name', 'role', 'alamat', 'email', 'password','image','no_telp'];

    public function orders() {
        return $this->hasMany(Order::class);
    }

    
    public function stock_sales() {
        return $this->hasOne(StockMovement::class,'sales_id','id');
    }

    public function reseller() {
        return $this->hasOne(Reseller::class);
    }


}
