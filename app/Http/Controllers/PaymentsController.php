<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order; // Sesuaikan dengan namespace model Anda
use App\Models\Payment; // Sesuaikan dengan namespace model Anda
use Midtrans\Config;
use Midtrans\Snap;

class PaymentsController extends Controller
{
    /**
     * Set up konfigurasi Midtrans saat controller diinisialisasi.
     */
    public function __construct()
    {
        // Set your Merchant Server Key
        Config::$serverKey = config('services.midtrans.server_key');
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment.
        Config::$isProduction = config('services.midtrans.is_production');
        // Set sanitization on (default)
        Config::$isSanitized = true;
        // Set 3DS transaction for credit card to true
        Config::$is3ds = true;
    }

    /**
     * Menampilkan halaman pembayaran dan membuat transaksi Midtrans.
     * Fungsi ini dipanggil saat user menekan tombol "Bayar Sekarang".
     */
    public function create(Request $request)
    {
        // Validasi request, pastikan order_id ada
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        // Ambil data order dari database bersama dengan relasi user
        $order = Order::with('user')->findOrFail($request->order_id);
        
        // Buat record pembayaran baru di database
        $payment = Payment::create([
            'order_id' => $order->id,
            'total_pembayaran' => $order->total_harga,
            'status_pembayaran' => 'menunggu',
            // midtrans_order_id harus unik, kita bisa gunakan kombinasi prefix dan ID pembayaran
            'midtrans_order_id' => 'PAY-' . uniqid() . '-' . $order->id, 
        ]);

        // Buat parameter untuk dikirim ke Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $payment->midtrans_order_id, // Gunakan ID unik dari tabel payments
                'gross_amount' => $order->total_harga,
            ],
            'customer_details' => [
                'first_name' => $order->user->name,
                'email' => $order->user->email,
                'phone' => $order->user->reseller->no_hp, // Asumsi user punya relasi ke reseller
            ],
        ];

        try {
            // Dapatkan Snap Token dari Midtrans
            $snapToken = Snap::getSnapToken($params);

            // Simpan Snap Token ke record pembayaran
            $payment->snap_token = $snapToken;
            $payment->save();

            // Kembalikan view dengan Snap Token
            return view('payments.checkout', [
                'snapToken' => $snapToken,
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            // Jika ada error dari Midtrans, kembalikan ke halaman sebelumnya dengan pesan error
            return redirect()->back()->with('error', 'Gagal membuat transaksi pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menangani notifikasi (webhook) dari Midtrans.
     * URL ini harus didaftarkan di dashboard Midtrans Anda.
     */
    public function notificationHandler(Request $request)
    {
        // Gunakan library Midtrans untuk mem-parsing notifikasi
        $notif = new \Midtrans\Notification();

        // Lakukan verifikasi signature key untuk keamanan (opsional tapi SANGAT direkomendasikan)
        // Cara verifikasi ini lebih aman daripada hanya mengandalkan status dari payload
        $signature_key = hash('sha512', $notif->order_id . $notif->status_code . $notif->gross_amount . config('services.midtrans.server_key'));

        if ($notif->signature_key != $signature_key) {
             return response()->json(['message' => 'Invalid signature'], 403);
        }

        $transaction_status = $notif->transaction_status;
        $payment_type = $notif->payment_type;
        $order_id = $notif->order_id; // Ini adalah midtrans_order_id yang kita buat

        // Cari data pembayaran di database berdasarkan midtrans_order_id
        $payment = Payment::where('midtrans_order_id', $order_id)->first();
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Update status pembayaran berdasarkan notifikasi
        if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
            $payment->status_pembayaran = 'selesai';
            // Lakukan aksi lain jika pembayaran berhasil
            // Contoh: kurangi stok, kirim email konfirmasi, buat data pengiriman
            // dispatch(new ProcessOrderJob($payment->order));
        } else if ($transaction_status == 'pending') {
            $payment->status_pembayaran = 'diproses';
        } else if ($transaction_status == 'deny' || $transaction_status == 'cancel' || $transaction_status == 'expire') {
            $payment->status_pembayaran = 'gagal';
        }

        // Simpan informasi tambahan
        $payment->metode_pembayaran = $payment_type;
        $payment->midtrans_transaction_id = $notif->transaction_id;
        $payment->raw_response = json_encode($notif->getResponse()); // Simpan seluruh response untuk audit
        $payment->save();

        return response()->json(['message' => 'Notification processed successfully.']);
    }
}