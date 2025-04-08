<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{
    use HasFactory;
    protected $table = 'resellers';
    protected $fillable = ['nama_toko', 'no_hp', 'status', 'user_id'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
