<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Http\Request;

class ResellersController extends Controller
{
   
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'nama_toko' => 'required|string|max:255',
        ]);
    
        // Cek apakah sudah ada request reseller yang masih diproses
        $sudahAda = Reseller::where('user_id', $validated['user_id'])
            ->where('status', 'proses')
            ->exists();
    
        if ($sudahAda) {
            return response()->json([
                'message' => 'Permintaan Anda sudah diproses sebelumnya. Harap tunggu persetujuan admin.',
            ], 409);
        }
    
        // Buat permintaan reseller (notifikasi akan dikirim otomatis dari model)
        $permintaan = Reseller::create([
            'user_id' => $validated['user_id'],
            'nama_toko' => $validated['nama_toko'],
            'status' => 'proses',
        ]);
    
        return response()->json([
            'message' => 'Permintaan berhasil dikirim!',
            'data' => $permintaan,
        ], 201);
    }
}
