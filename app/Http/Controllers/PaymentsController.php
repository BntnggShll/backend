<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use Midtrans\Notification;
class PaymentsController extends Controller
{
    public function callback(Request $request){
        $serverkey = config('midtrans.server_key');
        $hashed = hash("sha512", $request->order_id.$request->status_code.$request->gross_amout.$serverkey);
        if ($hashed == $request->signature_key) {
            if ($request->transaction_status == 'settlement') {
                $order = Order::find($request->order_id);
                $order->update(['status'=>'dibayar']);
            }
        }

    }
}
