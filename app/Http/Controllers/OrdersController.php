<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            ]);

            // Tambahkan item-item ke order
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $item['unit_id'],
                    'jumlah' => $item['quantity'],
                    'harga' => $item['price'],
                ]);
            }
            $shipment = Shipment::create([
                'order_id' => $order->id,
                'nama_penerima' => $request->name,
                'catatan' => $request->notes,
                'nomor_telp' => $request->phone,
                'alamat_pengantaran' => $request->alamat,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat.',
                'order' => $order->load('orderItems')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat order: ' . $e->getMessage()
            ], 500);
        }
    }
}
