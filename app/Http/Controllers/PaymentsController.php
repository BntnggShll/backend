<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Midtrans\Snap;
use Midtrans\Config;
use Midtrans\Transaction;

class PaymentsController extends Controller
{
    public function __construct()
    {
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }

    // 1. Create Snap Token
    public function createSnap(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'total' => 'required|numeric',
            'name' => 'required|string',
            'email' => 'required|email',
        ]);

        $order = Order::findOrFail($request->order_id);

        $payload = [
            'transaction_details' => [
                'order_id' => 'ORDER-' . $order->id . '-' . time(),
                'gross_amount' => $request->total,
            ],
            'customer_details' => [
                'first_name' => $request->name,
                'email' => $request->email,
            ]
        ];

        $snapToken = Snap::getSnapToken($payload);

        // Simpan transaksi
        $transaction = Payment::create([
            'order_id' => $order->id,
            'midtrans_order_id' => $payload['transaction_details']['order_id'],
            'snap_token' => $snapToken,
            'total_pembayaran' => $request->total,
            'metode_pembayaran' => 'midtrans',
        ]);

        return response()->json([
            'snap_token' => $snapToken,
            'transaction_id' => $transaction->id
        ]);
    }

    // 2. Handle Callback from Midtrans
    public function handleCallback(Request $request)
    {
        $notif = new \Midtrans\Notification();

        $midtransOrderId = $notif->order_id;
        $status = $notif->transaction_status;
        $paymentType = $notif->payment_type;
        $raw = json_encode($notif);

        $transaction = Payment::where('midtrans_order_id', $midtransOrderId)->first();

        if ($transaction) {
            $transaction->status_pembayaran = match ($status) {
                'capture', 'settlement' => 'selesai',
                'pending' => 'menunggu',
                'deny', 'cancel', 'expire' => 'gagal',
                default => $transaction->status_pembayaran
            };
            $transaction->raw_response = $raw;
            $transaction->save();

            return response()->json(['message' => 'Callback diterima'], 200);
        }

        return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
    }

    // 3. Check Status
    public function checkStatus($orderId)
    {
        $transaction = Payment::where('order_id', $orderId)->first();
        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        $status = Transaction::status($transaction->midtrans_order_id);
        return response()->json($status);
    }
}
