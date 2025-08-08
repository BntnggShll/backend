<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    
    use HasFactory,Notifiable,HasApiTokens;
    protected $table = 'users';
    protected $fillable = ['name', 'role', 'alamat', 'email', 'password','image','no_telp','email_verified_at'];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function orders() {
        return $this->hasMany(Order::class);
    }

    
    public function stock_sales() {
        return $this->hasOne(StockMovement::class,'sales_id','id');
    }

    public function reseller() {
        return $this->hasOne(Reseller::class,'user_id');
    }


}
