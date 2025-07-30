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
                        'order_id' => $order->order_number,
                        'gross_amount' => $request->total_amount,
                    ],
                    'customer_details' => [
                        'first_name' => $request->name,
                        'email' => Auth::user()->email,
                    ],
                    'callbacks' => [
                        'finish' => 'http://192.168.20.35:8080',
                    ],

                ];
                $snapToken = Snap::getSnapToken($payload);

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
}
