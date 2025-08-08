<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class Reseller extends Model
{
    use HasFactory;
    protected $table = 'resellers';
    protected $fillable = ['nama_toko', 'status', 'user_id'];

    public function user() {
        return $this->belongsTo(User::class,'user_id');
    }

    protected static function booted()
    {
        static::created(function ($reseller) {
            $admins = User::where('role', 'admin')->get();

            if ($admins->isNotEmpty()) {
                $customer = optional($reseller->user)->name ?? 'Customer Tidak Diketahui';
                $namaToko = $reseller->nama_toko;

                Notification::make()
                    ->title('Permintaan Reseller Baru')
                    ->body("{$customer} mengajukan permintaan menjadi reseller dengan nama toko: {$namaToko}.")
                    ->info()
                    ->sendToDatabase($admins);
            }
        });
    }
}
