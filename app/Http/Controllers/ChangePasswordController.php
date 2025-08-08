<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class ChangePasswordController extends Controller
{
    public function update(Request $request)
    {
        // $request->validate([
        //     'currentPassword' => 'required',
        //     'newPassword' => 'required|min:6|confirmed',
        // ]);

        $user = $request->user();

        if (!Hash::check($request->currentPassword, $user->password)) {
            return response()->json([
                'message' => 'Password saat ini salah.',
            ], 403);
        }

        $user->password = bcrypt($request->newPassword);
        $user->save();

        return response()->json([
            'message' => 'Password berhasil diubah.',
        ]);
    }
}
