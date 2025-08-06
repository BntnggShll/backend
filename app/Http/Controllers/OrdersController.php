<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Midtrans\Snap;
use Illuminate\Support\Str;
class OrdersController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.unit_id' => 'required|integer|exists:product_units,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'alamat' => 'required|string',
            'name' => 'required|string',
            'notes' => 'nullable|string',
            'phone' => 'required|string',
            'payment_method' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'shippingCost' => 'required|numeric'

        ]);

        try {
            DB::beginTransaction();

            // Buat order
            $order = Order::create([
                'user_id' => Auth::id(),
                'total_harga' => $request->total_amount,
                'shipping_cost' => $request->shippingCost,
                'order_number' => 'ORDER-' . time(),
            ]);

            // Tambahkan item-item ke order
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_unit_id' => $item['unit_id'],
                    'jumlah' => $item['quantity'],
                    'harga' => $item['price'],
                ]);
                StockMovement::create([
                    'product_unit_id' => $item['unit_id'],
                    'quantity' => -$item['quantity'],
                    'type' => 'out',
                ]);
            }
            $shipment = Shipment::create([
                'order_id' => $order->id,
                'nama_penerima' => $request->name,
                'catatan' => $request->notes,
                'nomor_telp' => $request->phone,
                'alamat_pengantaran' => $request->alamat,
                'perkiraan_pengiriman' => Carbon::now()->addDays(3)->toDateString(),
            ]);


            if ($request->payment_method === 'transfer') {
                $payload = [
                    'transaction_details' => [
                        'order_id' => $order->id . '-' . time(),
                        'gross_amount' => $request->total_amount,
                    ],
                    'customer_details' => [
                        'first_name' => $request->name,
                        'email' => Auth::user()->email,
                    ],
                    'callbacks' => [
                        'finish' => 'http://192.168.20.35:8080',
                    ],
                    'notification_url' => 'https://faster-limousines-stylish-area.trycloudflare.com/api/callback',

                ];
                $snapToken = Snap::getSnapToken($payload);
                $transaction_id = 'TRX-' . Str::random(4) . '-' . Str::random(8);
                // Simpan transaksi
                $transaction = Payment::create([
                    'order_id' => $order->id,
                    'midtrans_order_id' => $payload['transaction_details']['order_id'],
                    'snap_token' => $snapToken,
                    'total_pembayaran' => $request->total_amount,
                    'metode_pembayaran' => 'midtrans',
                ]);
            }


            DB::commit();

            return response()->json(array_merge([
                'success' => true,
                'message' => 'Order berhasil dibuat.',
                'order' => $order->load('orderItems.productunit.product', 'orderItems.productunit.unit', 'payments'),
            ], isset($transaction) ? [
                    'snap_token' => $snapToken,
                    'transaction_id' => $transaction->id,
                ] : []), 201);


        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        // Ambil semua order milik user yang sedang login
        $orders = Order::with([
            'orderItems.productunit.product',
            'orderItems.productunit.unit',
            'payments',
            'shipment'
        ])->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data order ditemukan.',
            'orders' => $orders
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::with(['orderItems', 'payments', 'shipment'])->where('id', $id)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        }

    
        try {
            DB::beginTransaction();

            // Update status order
            $order->status = 'batal';
            $order->save();

            // Kembalikan stok
            foreach ($order->orderItems as $item) {
                StockMovement::create([
                    'product_unit_id' => $item->product_unit_id,
                    'quantity' => $item->jumlah,
                    'type' => 'in',
                    'order_id' => $order->id, // pastikan kolom ini ada
                ]);
            }

            // Hapus shipment
            if ($order->shipment) {
                $order->shipment->delete();
            }

            // Hapus payment
            if ($order->payments && $order->payments->count() > 0) {
                $order->payments()->delete();
            }

            // Opsional: expire transaksi Midtrans
            if ($order->payments && count($order->payments)) {
                $midtransOrderId = $order->payments[0]->midtrans_order_id;
                try {
                    \Midtrans\Transaction::expire($midtransOrderId);
                } catch (\Exception $e) {
                    // Bisa log error di sini jika perlu
                }
            }

            DB::commit();

            return response()->json(['message' => 'Pesanan berhasil dibatalkan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membatalkan pesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

}
