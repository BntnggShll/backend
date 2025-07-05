<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    // Tipe primary key-nya bukan integer, melainkan string (UUID)
    protected $keyType = 'string';
    public $incrementing = false;

    // Izinkan casting kolom data dari JSON ke array
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];
}
