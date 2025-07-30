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

        $permintaan = Reseller::create([
            'user_id' => $validated['user_id'],
            'nama_toko' => $validated['nama_toko'],
        ]);
        $user = User::find($validated['user_id']);
        if ($user->role !== 'reseller') {
            $user->role = 'reseller';
            $user->save();
        }
        return response()->json([
            'message' => 'Permintaan berhasil dikirim!',
            'data' => $permintaan,
        ], 201);
    }
}
