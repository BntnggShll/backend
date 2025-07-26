<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ShippingRates;
use Illuminate\Http\Request;

class ShipmentsController extends Controller
{
    public function cost(Request $request)
    {
        $request->validate([
            'shipping_address' => 'required|string',
        ]);

        $shippingAddress = $request->shipping_address;

        // Coba cari nama daerah yang terdapat di dalam alamat
        $rate = ShippingRates::whereRaw("LOCATE(nama_daerah, ?) > 0", [$shippingAddress])->first();

        if ($rate) {
            return response()->json([
                'success' => true,
                'shipping_address' => $rate->nama_daerah, // bisa juga balikin hasil cocoknya
                'cost' => $rate->cost,
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Alamat tidak ditemukan dalam daftar ongkos kirim.',
            ], 404);
        }
    }

}
