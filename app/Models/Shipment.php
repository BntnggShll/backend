<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
class Shipment extends Model
{
    use HasFactory;
    protected $table = 'shipments';
    protected $fillable = ['order_id', 'sales_id','catatan','nomor_telp','nama_penerima','alamat_pengantaran','perkiraan_pengiriman', 'status_pengiriman'];

    public function order() {
        return $this->belongsTo(Order::class);
    }
    public function sales() {
        return $this->belongsTo(User::class);
    }
    protected static function booted()
    {
        static::created(function ($shipment) {
            // Cari semua user dengan role sales
            $recipients = User::where('role', 'sales')->get();

            if ($recipients->isNotEmpty()) {
                $orderId = $shipment->order_id;
                $customer = optional($shipment->order->user)->name ?? 'Customer Tidak Diketahui';
                $alamat = $shipment->alamat_pengantaran;

                Notification::make()
                    ->title('Pengiriman Baru')
                    ->body("Pesanan baru dari {$customer} akan dikirim ke alamat: {$alamat}. (Order ID: #{$orderId})")
                    ->success()
                    ->sendToDatabase($recipients);
            }
        });
    }
}
